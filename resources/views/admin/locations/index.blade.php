@extends('admin.layouts.app')

@section('title', 'Location Management')
@section('breadcrumb-parent', 'System')
@section('breadcrumb-current', 'Location Management')

@section('content')
<div x-data="locationManager()" x-init="init()">

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800">Location Management</h1>
        <p class="text-sm text-slate-500 mt-0.5">Manage Ghana regions, districts, and towns used for shipment addresses.</p>
    </div>

    {{-- Tab Switcher --}}
    <div class="flex gap-1 mb-5 bg-slate-100/80 p-1 rounded-xl w-fit">
        <button @click="switchTab('regions')"
                :class="activeTab === 'regions' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all">
            Regions <span x-text="'(' + regionStats.total + ')'" class="ml-1 opacity-60"></span>
        </button>
        <button @click="switchTab('districts')"
                :class="activeTab === 'districts' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all">
            Districts <span x-text="'(' + districtStats.total + ')'" class="ml-1 opacity-60"></span>
        </button>
        <button @click="switchTab('towns')"
                :class="activeTab === 'towns' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="px-4 py-2 text-xs font-semibold rounded-lg transition-all">
            Towns / Cities <span x-text="'(' + townStats.total + ')'" class="ml-1 opacity-60"></span>
        </button>
    </div>

    {{-- ─── REGIONS TAB ─────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'regions'" x-cloak>
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
            {{-- Card Header --}}
            <div class="px-6 py-5 border-b border-slate-200/50">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-violet-50">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Regions</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Administrative regions of Ghana</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="regionStats.total + ' Total'"></span>
                </div>
            </div>
            {{-- Controls --}}
            <div class="p-6 pb-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input x-model="regionSearch" @input.debounce.500ms="loadRegions()" type="text" placeholder="Search regions..."
                                   class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        {{-- Status Filter --}}
                        <div x-data="{ open: false }" class="relative w-full sm:w-48">
                            <button type="button" @click="open = !open"
                                    class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                <span x-text="regionStatusFilterName"></span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display:none">
                                <button type="button" @click="regionStatusFilter='';regionStatusFilterName='All statuses';regionPage=1;open=false"
                                        class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="regionStatusFilter===''?'bg-white/70 shadow-sm':''">
                                    <svg x-show="regionStatusFilter===''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    All statuses
                                </button>
                                <button type="button" @click="regionStatusFilter='active';regionStatusFilterName='Active';regionPage=1;open=false"
                                        class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="regionStatusFilter==='active'?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''">
                                    <svg x-show="regionStatusFilter==='active'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Active
                                </button>
                                <button type="button" @click="regionStatusFilter='inactive';regionStatusFilterName='Inactive';regionPage=1;open=false"
                                        class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="regionStatusFilter==='inactive'?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''">
                                    <svg x-show="regionStatusFilter==='inactive'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Inactive
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        {{-- View Columns --}}
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display:none">
                                <template x-for="col in regionColumns" :key="col.key">
                                    <button type="button" @click="regionVisible[col.key] = !regionVisible[col.key]"
                                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="regionVisible[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        {{-- Export --}}
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display:none">
                                <button type="button" @click="exportCSV('regions');open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @click="printTable('regions');open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Print
                                </button>
                            </div>
                        </div>
                        <button @click="openAddModal('regions')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Region
                        </button>
                    </div>
                </div>
            </div>
            {{-- Table --}}
            <div class="px-6 py-4">
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="loadingRegions" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 rounded-xl" style="display:none"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="regionVisible.name" @click="sortR('name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center gap-1">NAME <svg class="w-2.5 h-2.5" :class="regionSortBy==='name'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="regionVisible.code" @click="sortR('code')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center gap-1">CODE <svg class="w-2.5 h-2.5" :class="regionSortBy==='code'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="regionVisible.districts" @click="sortR('districts_count')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center justify-center gap-1">DISTRICTS <svg class="w-2.5 h-2.5" :class="regionSortBy==='districts_count'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="regionVisible.towns" @click="sortR('locations_count')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                        <div class="flex items-center justify-center gap-1">TOWNS <svg class="w-2.5 h-2.5" :class="regionSortBy==='locations_count'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                    </th>
                                    <th x-show="regionVisible.status" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="!loadingRegions && getSortedRegions().length === 0">
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">No regions found.</td></tr>
                                </template>
                                <template x-for="r in getPagedRegions()" :key="r.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="regionVisible.name" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="r.name"></td>
                                        <td x-show="regionVisible.code" class="px-4 py-2.5 whitespace-nowrap text-xs font-mono text-slate-600" x-text="r.code"></td>
                                        <td x-show="regionVisible.districts" class="px-4 py-2.5 whitespace-nowrap text-xs text-center">
                                            <button @click="filterDistricts(r)" class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-slate-100 text-slate-700 text-[11px] font-semibold hover:bg-slate-200 px-2" x-text="r.districts_count"></button>
                                        </td>
                                        <td x-show="regionVisible.towns" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600 text-center" x-text="r.locations_count"></td>
                                        <td x-show="regionVisible.status" class="px-4 py-2.5 whitespace-nowrap text-xs">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                  :class="r.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                                  x-text="r.is_active ? 'Active' : 'Inactive'"></span>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                            <div class="inline-flex items-center gap-1">
                                                <button @click="openEditModal('regions', r)" class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </button>
                                                <button @click="toggleItem('regions', r)" class="p-1.5 rounded-lg transition-colors"
                                                        :class="r.is_active ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' : 'text-slate-400 hover:text-teal-600 hover:bg-teal-50'"
                                                        :title="r.is_active ? 'Deactivate' : 'Activate'">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                </button>
                                                <button @click="deleteItem('regions', r)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
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
                                Showing <span x-text="regionPageFrom()"></span> to <span x-text="regionPageTo()"></span> of <span x-text="getSortedRegions().length"></span> results
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open"
                                                class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="regionPerPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition
                                             class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display:none">
                                            <button type="button" @click="setRegionPerPage(10);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="regionPerPage==10?'bg-slate-100/70':''">10</button>
                                            <button type="button" @click="setRegionPerPage(25);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="regionPerPage==25?'bg-slate-100/70':''">25</button>
                                            <button type="button" @click="setRegionPerPage(50);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="regionPerPage==50?'bg-slate-100/70':''">50</button>
                                            <button type="button" @click="setRegionPerPage(100);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="regionPerPage==100?'bg-slate-100/70':''">100</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="regionPage"></span> of <span x-text="regionLastPage()"></span></div>
                                <div class="flex space-x-1">
                                    <button @click="regionFirstPage()" :disabled="regionPage===1" :class="regionPage===1?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @click="regionPrevPage()" :disabled="regionPage===1" :class="regionPage===1?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @click="regionNextPage()" :disabled="regionPage>=regionLastPage()" :class="regionPage>=regionLastPage()?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @click="regionLastPageNav()" :disabled="regionPage>=regionLastPage()" :class="regionPage>=regionLastPage()?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── DISTRICTS TAB ───────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'districts'" x-cloak>
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
            {{-- Card Header --}}
            <div class="px-6 py-5 border-b border-slate-200/50">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-sky-50">
                            <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Districts</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Districts within each region</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="districtStats.total + ' Total'"></span>
                </div>
            </div>
            {{-- Controls --}}
            <div class="p-6 pb-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative flex-1 max-w-xs">
                            <input x-model="districtSearch" @input.debounce.500ms="loadDistricts()" type="text" placeholder="Search districts..."
                                   class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <div x-data="{ open: false }" class="relative w-full sm:w-48">
                            <button type="button" @click="open = !open"
                                    class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                <span x-text="districtRegionFilter ? (regions.find(r=>r.id==districtRegionFilter)||{name:'Region'}).name : 'All Regions'"></span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-56 overflow-y-auto" style="display:none">
                                <button type="button" @click="districtRegionFilter='';loadDistricts();open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="districtRegionFilter===''?'bg-white/70 shadow-sm':''">All Regions</button>
                                <template x-for="r in regions" :key="r.id">
                                    <button type="button" @click="districtRegionFilter=r.id;loadDistricts();open=false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="districtRegionFilter==r.id?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''" x-text="r.name"></button>
                                </template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative w-full sm:w-44">
                            <button type="button" @click="open = !open"
                                    class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                <span x-text="districtStatusFilterName"></span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display:none">
                                <button type="button" @click="districtStatusFilter='';districtStatusFilterName='All statuses';districtPage=1;open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="districtStatusFilter===''?'bg-white/70 shadow-sm':''"><svg x-show="districtStatusFilter===''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>All statuses</button>
                                <button type="button" @click="districtStatusFilter='active';districtStatusFilterName='Active';districtPage=1;open=false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="districtStatusFilter==='active'?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''"><svg x-show="districtStatusFilter==='active'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Active</button>
                                <button type="button" @click="districtStatusFilter='inactive';districtStatusFilterName='Inactive';districtPage=1;open=false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="districtStatusFilter==='inactive'?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''"><svg x-show="districtStatusFilter==='inactive'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Inactive</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display:none">
                                <template x-for="col in districtColumns" :key="col.key">
                                    <button type="button" @click="districtVisible[col.key] = !districtVisible[col.key]" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="districtVisible[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display:none">
                                <button type="button" @click="exportCSV('districts');open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @click="printTable('districts');open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                        <button @click="openAddModal('districts')" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add District
                        </button>
                    </div>
                </div>
            </div>
            {{-- Table --}}
            <div class="px-6 py-4">
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="loadingDistricts" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 rounded-xl" style="display:none"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="districtVisible.name" @click="sortD('name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center gap-1">DISTRICT <svg class="w-2.5 h-2.5" :class="districtSortBy==='name'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="districtVisible.code" @click="sortD('code')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center gap-1">CODE <svg class="w-2.5 h-2.5" :class="districtSortBy==='code'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="districtVisible.region" @click="sortD('region_name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center gap-1">REGION <svg class="w-2.5 h-2.5" :class="districtSortBy==='region_name'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="districtVisible.towns" @click="sortD('locations_count')" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center justify-center gap-1">TOWNS <svg class="w-2.5 h-2.5" :class="districtSortBy==='locations_count'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="districtVisible.status" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="!loadingDistricts && getSortedDistricts().length === 0">
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">No districts found.</td></tr>
                                </template>
                                <template x-for="d in getPagedDistricts()" :key="d.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="districtVisible.name" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="d.name"></td>
                                        <td x-show="districtVisible.code" class="px-4 py-2.5 whitespace-nowrap text-xs font-mono text-slate-600" x-text="d.code"></td>
                                        <td x-show="districtVisible.region" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="d.region_name"></td>
                                        <td x-show="districtVisible.towns" class="px-4 py-2.5 whitespace-nowrap text-xs text-center">
                                            <button @click="filterTowns(d)" class="inline-flex items-center justify-center min-w-[28px] h-6 rounded-full bg-slate-100 text-slate-700 text-[11px] font-semibold hover:bg-slate-200 px-2" x-text="d.locations_count"></button>
                                        </td>
                                        <td x-show="districtVisible.status" class="px-4 py-2.5 whitespace-nowrap text-xs">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="d.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" x-text="d.is_active ? 'Active' : 'Inactive'"></span>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                            <div class="inline-flex items-center gap-1">
                                                <button @click="openEditModal('districts', d)" class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                                <button @click="toggleItem('districts', d)" class="p-1.5 rounded-lg transition-colors" :class="d.is_active ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' : 'text-slate-400 hover:text-teal-600 hover:bg-teal-50'" :title="d.is_active ? 'Deactivate' : 'Activate'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg></button>
                                                <button @click="deleteItem('districts', d)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
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
                                Showing <span x-text="districtPageFrom()"></span> to <span x-text="districtPageTo()"></span> of <span x-text="getSortedDistricts().length"></span> results
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open"
                                                class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="districtPerPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition
                                             class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display:none">
                                            <button type="button" @click="setDistrictPerPage(10);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="districtPerPage==10?'bg-slate-100/70':''">10</button>
                                            <button type="button" @click="setDistrictPerPage(25);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="districtPerPage==25?'bg-slate-100/70':''">25</button>
                                            <button type="button" @click="setDistrictPerPage(50);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="districtPerPage==50?'bg-slate-100/70':''">50</button>
                                            <button type="button" @click="setDistrictPerPage(100);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="districtPerPage==100?'bg-slate-100/70':''">100</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="districtPage"></span> of <span x-text="districtLastPage()"></span></div>
                                <div class="flex space-x-1">
                                    <button @click="districtFirstPage()" :disabled="districtPage===1" :class="districtPage===1?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @click="districtPrevPage()" :disabled="districtPage===1" :class="districtPage===1?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @click="districtNextPage()" :disabled="districtPage>=districtLastPage()" :class="districtPage>=districtLastPage()?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @click="districtLastPageNav()" :disabled="districtPage>=districtLastPage()" :class="districtPage>=districtLastPage()?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── TOWNS TAB ───────────────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'towns'" x-cloak>
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
            {{-- Card Header --}}
            <div class="px-6 py-5 border-b border-slate-200/50">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Towns / Cities</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Towns and cities within each district</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="townStats.total + ' Total'"></span>
                </div>
            </div>
            {{-- Controls --}}
            <div class="p-6 pb-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center flex-wrap">
                        <div class="relative max-w-xs flex-1">
                            <input x-model="townSearch" @input.debounce.500ms="townStats.offset=0;loadTowns()" type="text" placeholder="Search towns..."
                                   class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                            <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <div x-data="{ open: false }" class="relative w-full sm:w-44">
                            <button type="button" @click="open = !open" class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                <span x-text="townRegionFilter ? (regions.find(r=>r.id==townRegionFilter)||{name:'Region'}).name : 'All Regions'"></span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-56 overflow-y-auto" style="display:none">
                                <button type="button" @click="townRegionFilter='';townDistrictFilter='';townStats.offset=0;loadTownDistricts();loadTowns();open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70">All Regions</button>
                                <template x-for="r in regions" :key="r.id">
                                    <button type="button" @click="townRegionFilter=r.id;townDistrictFilter='';townStats.offset=0;loadTownDistricts();loadTowns();open=false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="townRegionFilter==r.id?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''" x-text="r.name"></button>
                                </template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative w-full sm:w-44">
                            <button type="button" @click="open = !open" class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                <span x-text="townDistrictFilter ? (townFilterDistricts.find(d=>d.id==townDistrictFilter)||{name:'District'}).name : 'All Districts'"></span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-56 overflow-y-auto" style="display:none">
                                <button type="button" @click="townDistrictFilter='';townStats.offset=0;loadTowns();open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70">All Districts</button>
                                <template x-for="d in townFilterDistricts" :key="d.id">
                                    <button type="button" @click="townDistrictFilter=d.id;townStats.offset=0;loadTowns();open=false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="townDistrictFilter==d.id?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''" x-text="d.name"></button>
                                </template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative w-full sm:w-44">
                            <button type="button" @click="open = !open" class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                <span x-text="townStatusFilterName"></span>
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display:none">
                                <button type="button" @click="townStatusFilter='';townStatusFilterName='All statuses';townStats.offset=0;loadTowns();open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="townStatusFilter===''?'bg-white/70 shadow-sm':''"><svg x-show="townStatusFilter===''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>All statuses</button>
                                <button type="button" @click="townStatusFilter='active';townStatusFilterName='Active';townStats.offset=0;loadTowns();open=false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="townStatusFilter==='active'?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''"><svg x-show="townStatusFilter==='active'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Active</button>
                                <button type="button" @click="townStatusFilter='inactive';townStatusFilterName='Inactive';townStats.offset=0;loadTowns();open=false" class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70" :class="townStatusFilter==='inactive'?'bg-white/70 shadow-sm ring-1 ring-slate-200/60':''"><svg x-show="townStatusFilter==='inactive'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Inactive</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display:none">
                                <template x-for="col in townColumns" :key="col.key">
                                    <button type="button" @click="townVisible[col.key] = !townVisible[col.key]" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="townVisible[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display:none">
                                <button type="button" @click="exportCSV('towns');open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>CSV</button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @click="printTable('towns');open=false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70"><svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>Print</button>
                            </div>
                        </div>
                        <button @click="openAddModal('towns')" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Town
                        </button>
                    </div>
                </div>
            </div>
            {{-- Table --}}
            <div class="px-6 py-4">
                <div class="rounded-xl border border-slate-200/50 relative">
                    <div x-show="loadingTowns" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 rounded-xl" style="display:none"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px] divide-y divide-slate-200/50 text-xs">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th x-show="townVisible.name" @click="sortT('name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center gap-1">TOWN / CITY <svg class="w-2.5 h-2.5" :class="townSortBy==='name'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="townVisible.type" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">TYPE</th>
                                    <th x-show="townVisible.district" @click="sortT('district_name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center gap-1">DISTRICT <svg class="w-2.5 h-2.5" :class="townSortBy==='district_name'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="townVisible.region" @click="sortT('region_name')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer"><div class="flex items-center gap-1">REGION <svg class="w-2.5 h-2.5" :class="townSortBy==='region_name'?'text-slate-600':'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div></th>
                                    <th x-show="townVisible.status" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">STATUS</th>
                                    <th class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-transparent divide-y divide-slate-100/50">
                                <template x-if="!loadingTowns && towns.length === 0">
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">No towns found.</td></tr>
                                </template>
                                <template x-for="t in towns" :key="t.id">
                                    <tr class="hover:bg-slate-50/70">
                                        <td x-show="townVisible.name" class="px-4 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-900" x-text="t.name"></td>
                                        <td x-show="townVisible.type" class="px-4 py-2.5 whitespace-nowrap text-xs">
                                            <span :class="{'bg-purple-100 text-purple-700':t.type==='city','bg-blue-100 text-blue-700':t.type==='town','bg-slate-100 text-slate-600':t.type==='suburb'||t.type==='village'}"
                                                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize" x-text="t.type"></span>
                                        </td>
                                        <td x-show="townVisible.district" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="t.district_name"></td>
                                        <td x-show="townVisible.region" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="t.region_name"></td>
                                        <td x-show="townVisible.status" class="px-4 py-2.5 whitespace-nowrap text-xs">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="t.is_active?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-600'" x-text="t.is_active?'Active':'Inactive'"></span>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-center text-xs font-medium">
                                            <div class="inline-flex items-center gap-1">
                                                <button @click="openEditModal('towns', t)" class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                                <button @click="toggleItem('towns', t)" class="p-1.5 rounded-lg transition-colors" :class="t.is_active?'text-slate-400 hover:text-amber-600 hover:bg-amber-50':'text-slate-400 hover:text-teal-600 hover:bg-teal-50'" :title="t.is_active?'Deactivate':'Activate'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg></button>
                                                <button @click="deleteItem('towns', t)" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    {{-- Pagination --}}
                    <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">
                                Showing <span x-text="townStats.total > 0 ? townStats.offset + 1 : 0"></span> to <span x-text="Math.min(townStats.offset + townPerPage, townStats.total)"></span> of <span x-text="townStats.total"></span> results
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open"
                                                class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                            <span x-text="townPerPage"></span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition
                                             class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display:none">
                                            <button type="button" @click="setTownPerPage(10);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="townPerPage==10?'bg-slate-100/70':''">10</button>
                                            <button type="button" @click="setTownPerPage(25);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="townPerPage==25?'bg-slate-100/70':''">25</button>
                                            <button type="button" @click="setTownPerPage(50);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="townPerPage==50?'bg-slate-100/70':''">50</button>
                                            <button type="button" @click="setTownPerPage(100);open=false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="townPerPage==100?'bg-slate-100/70':''">100</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-xs font-medium text-slate-600">Page <span x-text="townCurrentPage()"></span> of <span x-text="townLastPage()"></span></div>
                                <div class="flex space-x-1">
                                    <button @click="townFirstPageNav()" :disabled="townCurrentPage()===1" :class="townCurrentPage()===1?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                    <button @click="townPrevPageNav()" :disabled="townCurrentPage()===1" :class="townCurrentPage()===1?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button @click="townNextPageNav()" :disabled="townCurrentPage()>=townLastPage()" :class="townCurrentPage()>=townLastPage()?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                    <button @click="townLastPageNav()" :disabled="townCurrentPage()>=townLastPage()" :class="townCurrentPage()>=townLastPage()?'opacity-50 cursor-not-allowed':'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── MODAL ───────────────────────────────────────────────────────────────── --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeModal()">
        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="modalOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 @click.stop class="relative w-full max-w-md bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/50">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/50">
                    <h3 class="text-sm font-bold text-slate-800" x-text="modalTitle"></h3>
                    <button @click="closeModal()" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-4 space-y-4">
                    <div x-show="modalError" class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700" x-text="modalError"></div>
                    <template x-if="modalType === 'regions'">
                        <div class="space-y-3">
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Region Name <span class="text-rose-500">*</span></label><input x-model="form.name" type="text" placeholder="e.g. Greater Accra" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30"></div>
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Code <span class="text-rose-500">*</span></label><input x-model="form.code" type="text" placeholder="e.g. GAR" maxlength="20" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30 uppercase"><p class="mt-1 text-[10px] text-slate-400">Short unique code</p></div>
                        </div>
                    </template>
                    <template x-if="modalType === 'districts'">
                        <div class="space-y-3">
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Region <span class="text-rose-500">*</span></label><select x-model="form.region_id" @change="loadModalDistricts()" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30"><option value="">Select region...</option><template x-for="r in regions" :key="r.id"><option :value="r.id" x-text="r.name"></option></template></select></div>
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">District Name <span class="text-rose-500">*</span></label><input x-model="form.name" type="text" placeholder="e.g. Accra Metropolitan" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30"></div>
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Code <span class="text-rose-500">*</span></label><input x-model="form.code" type="text" placeholder="e.g. GAR-AMA" maxlength="30" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30 uppercase"></div>
                        </div>
                    </template>
                    <template x-if="modalType === 'towns'">
                        <div class="space-y-3">
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Region <span class="text-rose-500">*</span></label><select x-model="form.region_id" @change="loadModalDistricts()" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30"><option value="">Select region...</option><template x-for="r in regions" :key="r.id"><option :value="r.id" x-text="r.name"></option></template></select></div>
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">District <span class="text-rose-500">*</span></label><select x-model="form.district_id" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30"><option value="">Select district...</option><template x-for="d in modalDistricts" :key="d.id"><option :value="d.id" x-text="d.name"></option></template></select></div>
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Town / City Name <span class="text-rose-500">*</span></label><input x-model="form.name" type="text" placeholder="e.g. Kasoa" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30"></div>
                            <div><label class="block text-xs font-semibold text-slate-600 mb-1">Type <span class="text-rose-500">*</span></label><select x-model="form.type" class="w-full h-9 px-3 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400/30"><option value="town">Town</option><option value="city">City</option><option value="suburb">Suburb</option><option value="village">Village</option></select></div>
                        </div>
                    </template>
                </div>
                <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-200/50">
                    <button @click="closeModal()" class="h-9 px-4 text-xs font-medium text-slate-600 hover:text-slate-900 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
                    <button @click="saveModal()" :disabled="saving" class="h-9 px-5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-all disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="saving ? 'Saving...' : (editingId ? 'Update' : 'Create')"></span>
                    </button>
                </div>
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
        // Regions
        regions: [], loadingRegions: false, regionSearch: '',
        regionStats: { total: 0 },
        regionPerPage: 25, regionPage: 1,
        regionStatusFilter: '', regionStatusFilterName: 'All statuses',
        regionSortBy: 'name', regionSortDir: 'asc',
        regionColumns: [{ key:'name', label:'Region' },{ key:'code', label:'Code' },{ key:'districts', label:'Districts' },{ key:'towns', label:'Towns' },{ key:'status', label:'Status' }],
        regionVisible: { name:true, code:true, districts:true, towns:true, status:true },
        // Districts
        districts: [], loadingDistricts: false, districtSearch: '', districtRegionFilter: '',
        districtStats: { total: 0 },
        districtPerPage: 25, districtPage: 1,
        districtStatusFilter: '', districtStatusFilterName: 'All statuses',
        districtSortBy: 'name', districtSortDir: 'asc',
        districtColumns: [{ key:'name', label:'District' },{ key:'code', label:'Code' },{ key:'region', label:'Region' },{ key:'towns', label:'Towns' },{ key:'status', label:'Status' }],
        districtVisible: { name:true, code:true, region:true, towns:true, status:true },
        // Towns
        towns: [], loadingTowns: false, townSearch: '', townRegionFilter: '', townDistrictFilter: '',
        townFilterDistricts: [],
        townStats: { total: 0, limit: 50, offset: 0 },
        townPerPage: 50,
        townStatusFilter: '', townStatusFilterName: 'All statuses',
        townSortBy: 'name', townSortDir: 'asc',
        townColumns: [{ key:'name', label:'Town / City' },{ key:'type', label:'Type' },{ key:'district', label:'District' },{ key:'region', label:'Region' },{ key:'status', label:'Status' }],
        townVisible: { name:true, type:true, district:true, region:true, status:true },
        // Modal
        modalOpen: false, modalType: '', modalTitle: '', editingId: null,
        form: { name: '', code: '', region_id: '', district_id: '', type: 'town' },
        modalDistricts: [], modalError: '', saving: false,

        async init() {
            await this.loadRegions();
            await this.loadDistricts();
            await this.loadTowns();
        },

        switchTab(tab) { this.activeTab = tab; },

        // Sort helpers
        sortR(key) { this.regionSortDir = this.regionSortBy === key ? (this.regionSortDir === 'asc' ? 'desc' : 'asc') : 'asc'; this.regionSortBy = key; this.regionPage = 1; },
        sortD(key) { this.districtSortDir = this.districtSortBy === key ? (this.districtSortDir === 'asc' ? 'desc' : 'asc') : 'asc'; this.districtSortBy = key; this.districtPage = 1; },
        sortT(key) { this.townSortDir = this.townSortBy === key ? (this.townSortDir === 'asc' ? 'desc' : 'asc') : 'asc'; this.townSortBy = key; },

        _sortArr(arr, key, dir) {
            return [...arr].sort((a, b) => {
                const av = a[key] ?? '', bv = b[key] ?? '';
                const cmp = typeof av === 'number' ? av - bv : String(av).localeCompare(String(bv));
                return dir === 'asc' ? cmp : -cmp;
            });
        },

        getSortedRegions() {
            let arr = this.regionStatusFilter ? this.regions.filter(r => this.regionStatusFilter === 'active' ? r.is_active : !r.is_active) : this.regions;
            return this._sortArr(arr, this.regionSortBy, this.regionSortDir);
        },

        getSortedDistricts() {
            let arr = this.districtStatusFilter ? this.districts.filter(d => this.districtStatusFilter === 'active' ? d.is_active : !d.is_active) : this.districts;
            return this._sortArr(arr, this.districtSortBy, this.districtSortDir);
        },

        // Region pagination
        getPagedRegions()   { const arr = this.getSortedRegions(); return arr.slice((this.regionPage-1)*this.regionPerPage, this.regionPage*this.regionPerPage); },
        regionLastPage()    { return Math.max(1, Math.ceil(this.getSortedRegions().length / this.regionPerPage)); },
        regionPageFrom()    { return this.getSortedRegions().length > 0 ? (this.regionPage-1)*this.regionPerPage + 1 : 0; },
        regionPageTo()      { return Math.min(this.regionPage*this.regionPerPage, this.getSortedRegions().length); },
        regionFirstPage()   { this.regionPage = 1; },
        regionPrevPage()    { if (this.regionPage > 1) this.regionPage--; },
        regionNextPage()    { if (this.regionPage < this.regionLastPage()) this.regionPage++; },
        regionLastPageNav() { this.regionPage = this.regionLastPage(); },
        setRegionPerPage(n) { this.regionPerPage = n; this.regionPage = 1; },

        // District pagination
        getPagedDistricts()   { const arr = this.getSortedDistricts(); return arr.slice((this.districtPage-1)*this.districtPerPage, this.districtPage*this.districtPerPage); },
        districtLastPage()    { return Math.max(1, Math.ceil(this.getSortedDistricts().length / this.districtPerPage)); },
        districtPageFrom()    { return this.getSortedDistricts().length > 0 ? (this.districtPage-1)*this.districtPerPage + 1 : 0; },
        districtPageTo()      { return Math.min(this.districtPage*this.districtPerPage, this.getSortedDistricts().length); },
        districtFirstPage()   { this.districtPage = 1; },
        districtPrevPage()    { if (this.districtPage > 1) this.districtPage--; },
        districtNextPage()    { if (this.districtPage < this.districtLastPage()) this.districtPage++; },
        districtLastPageNav() { this.districtPage = this.districtLastPage(); },
        setDistrictPerPage(n) { this.districtPerPage = n; this.districtPage = 1; },

        // Town pagination
        townCurrentPage() { return this.townPerPage > 0 ? Math.floor(this.townStats.offset / this.townPerPage) + 1 : 1; },
        townLastPage()    { return Math.max(1, Math.ceil(this.townStats.total / this.townPerPage)); },
        townFirstPageNav() { this.townStats.offset = 0; this.townStats.limit = this.townPerPage; this.loadTowns(); },
        townPrevPageNav()  { this.townStats.offset = Math.max(0, this.townStats.offset - this.townPerPage); this.townStats.limit = this.townPerPage; this.loadTowns(); },
        townNextPageNav()  { if (this.townCurrentPage() < this.townLastPage()) { this.townStats.offset += this.townPerPage; this.townStats.limit = this.townPerPage; this.loadTowns(); } },
        townLastPageNav()  { this.townStats.offset = (this.townLastPage() - 1) * this.townPerPage; this.townStats.limit = this.townPerPage; this.loadTowns(); },
        setTownPerPage(n)  { this.townPerPage = n; this.townStats.limit = n; this.townStats.offset = 0; this.loadTowns(); },

        // Data loaders
        async loadRegions() {
            this.regionPage = 1;
            this.loadingRegions = true;
            try {
                const p = new URLSearchParams();
                if (this.regionSearch) p.set('search', this.regionSearch);
                const res = await this._fetch(`/admin/locations-data/regions?${p}`);
                this.regions = res.data.regions; this.regionStats.total = res.data.total;
            } finally { this.loadingRegions = false; }
        },

        filterDistricts(region) { this.districtRegionFilter = String(region.id); this.switchTab('districts'); this.loadDistricts(); },

        async loadDistricts() {
            this.districtPage = 1;
            this.loadingDistricts = true;
            try {
                const p = new URLSearchParams();
                if (this.districtSearch) p.set('search', this.districtSearch);
                if (this.districtRegionFilter) p.set('region_id', this.districtRegionFilter);
                const res = await this._fetch(`/admin/locations-data/districts?${p}`);
                this.districts = res.data.districts; this.districtStats.total = res.data.total;
            } finally { this.loadingDistricts = false; }
        },

        filterTowns(district) { this.townRegionFilter = String(district.region_id); this.townDistrictFilter = String(district.id); this.switchTab('towns'); this.loadTownDistricts(); this.loadTowns(); },

        async loadTowns() {
            this.loadingTowns = true;
            try {
                const p = new URLSearchParams();
                if (this.townSearch) p.set('search', this.townSearch);
                if (this.townRegionFilter) p.set('region_id', this.townRegionFilter);
                if (this.townDistrictFilter) p.set('district_id', this.townDistrictFilter);
                if (this.townStatusFilter) p.set('status', this.townStatusFilter);
                p.set('limit', this.townStats.limit); p.set('offset', this.townStats.offset);
                const res = await this._fetch(`/admin/locations-data/towns?${p}`);
                this.towns = res.data.towns; this.townStats.total = res.data.total; this.townStats.limit = res.data.limit;
            } finally { this.loadingTowns = false; }
        },

        async loadTownDistricts() {
            if (!this.townRegionFilter) { this.townFilterDistricts = []; return; }
            const res = await this._fetch(`/admin/locations-data/districts?region_id=${this.townRegionFilter}`);
            this.townFilterDistricts = res.data.districts;
        },

        // Modal
        openAddModal(type) {
            this.modalType = type; this.editingId = null; this.modalError = '';
            this.form = { name: '', code: '', region_id: '', district_id: '', type: 'town' };
            this.modalDistricts = [];
            this.modalTitle = type === 'regions' ? 'Add New Region' : type === 'districts' ? 'Add New District' : 'Add New Town';
            this.modalOpen = true;
        },

        openEditModal(type, item) {
            this.modalType = type; this.editingId = item.id; this.modalError = ''; this.modalDistricts = [];
            this.modalTitle = type === 'regions' ? 'Edit Region' : type === 'districts' ? 'Edit District' : 'Edit Town';
            if (type === 'regions') { this.form = { name: item.name, code: item.code, region_id: '', district_id: '', type: 'town' }; }
            else if (type === 'districts') { this.form = { name: item.name, code: item.code, region_id: String(item.region_id), district_id: '', type: 'town' }; this.loadModalDistricts(); }
            else { this.form = { name: item.name, code: '', region_id: String(item.region_id), district_id: String(item.district_id), type: item.type }; this.loadModalDistricts(); }
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
                const seg = this.modalType === 'regions' ? 'regions' : this.modalType === 'districts' ? 'districts' : 'towns';
                const url = this.editingId ? `/admin/locations/${seg}/${this.editingId}` : `/admin/locations/${seg}`;
                const payload = {};
                if (this.modalType === 'regions') { payload.name = this.form.name; payload.code = this.form.code; }
                else if (this.modalType === 'districts') { payload.name = this.form.name; payload.code = this.form.code; payload.region_id = this.form.region_id; }
                else { payload.name = this.form.name; payload.type = this.form.type; payload.district_id = this.form.district_id; }
                const res = await this._fetch(url, { method: this.editingId ? 'PUT' : 'POST', body: JSON.stringify(payload) });
                if (!res.success) { this.modalError = res.message || 'Failed to save.'; return; }
                this.closeModal(); this._toast(res.message, 'success');
                if (this.modalType === 'regions') this.loadRegions();
                else if (this.modalType === 'districts') { this.loadDistricts(); this.loadRegions(); }
                else { this.loadTowns(); this.loadDistricts(); }
            } catch (e) { this.modalError = e.message || 'An error occurred.'; }
            finally { this.saving = false; }
        },

        async toggleItem(type, item) {
            const seg = type === 'regions' ? 'regions' : type === 'districts' ? 'districts' : 'towns';
            const res = await this._fetch(`/admin/locations/${seg}/${item.id}/toggle`, { method: 'PATCH' });
            if (res.success) { item.is_active = res.data.is_active; this._toast(res.message, 'success'); }
            else this._toast(res.message || 'Failed.', 'error');
        },

        async deleteItem(type, item) {
            const label = type === 'regions' ? item.name + ' (Region)' : type === 'districts' ? item.name + ' (District)' : item.name;
            if (!confirm(`Delete "${label}"? This cannot be undone.`)) return;
            const seg = type === 'regions' ? 'regions' : type === 'districts' ? 'districts' : 'towns';
            const res = await this._fetch(`/admin/locations/${seg}/${item.id}`, { method: 'DELETE' });
            if (res.success) {
                this._toast(res.message, 'success');
                if (type === 'regions') this.loadRegions();
                else if (type === 'districts') { this.loadDistricts(); this.loadRegions(); }
                else { this.loadTowns(); this.loadDistricts(); }
            } else this._toast(res.message || 'Cannot delete.', 'error');
        },

        exportCSV(tab) {
            let headers, rows, filename;
            if (tab === 'regions') { headers = ['Name','Code','Districts','Towns','Status']; rows = this.getSortedRegions().map(r => [r.name, r.code, r.districts_count, r.locations_count, r.is_active?'Active':'Inactive']); filename = 'regions.csv'; }
            else if (tab === 'districts') { headers = ['Name','Code','Region','Towns','Status']; rows = this.getSortedDistricts().map(d => [d.name, d.code, d.region_name, d.locations_count, d.is_active?'Active':'Inactive']); filename = 'districts.csv'; }
            else { headers = ['Name','Type','District','Region','Status']; rows = this.towns.map(t => [t.name, t.type, t.district_name, t.region_name, t.is_active?'Active':'Inactive']); filename = 'towns.csv'; }
            const csv = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c??'').replace(/"/g,'""')}"`).join(','))].join('\n');
            const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([csv],{type:'text/csv'})); a.download = filename;
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
        },

        printTable(tab) {
            let title, headers, rows;
            if (tab === 'regions') { title='Regions'; headers=['Name','Code','Districts','Towns','Status']; rows=this.getSortedRegions().map(r=>[r.name,r.code,r.districts_count,r.locations_count,r.is_active?'Active':'Inactive']); }
            else if (tab === 'districts') { title='Districts'; headers=['Name','Code','Region','Towns','Status']; rows=this.getSortedDistricts().map(d=>[d.name,d.code,d.region_name,d.locations_count,d.is_active?'Active':'Inactive']); }
            else { title='Towns / Cities'; headers=['Name','Type','District','Region','Status']; rows=this.towns.map(t=>[t.name,t.type,t.district_name,t.region_name,t.is_active?'Active':'Inactive']); }
            const w = window.open('','_blank'); if (!w) { alert('Pop-up blocked.'); return; }
            w.document.title = title + ' Export';
            w.document.body.innerHTML = `<style>body{font-family:system-ui,sans-serif;padding:20px}h1{font-size:20px;color:#1e293b;margin-bottom:4px}p{font-size:12px;color:#64748b;margin-bottom:16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #e2e8f0;padding:8px 12px;text-align:left;font-size:11px}th{background:#f1f5f9;font-weight:600;color:#475569}tr:nth-child(even){background:#f8fafc}</style><h1>${title}</h1><p>Generated on ${new Date().toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</p><table><thead><tr>${headers.map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>${rows.map(r=>`<tr>${r.map(c=>`<td>${c??'-'}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
            setTimeout(() => w.print(), 250);
        },

        async _fetch(url, options = {}) {
            const res = await fetch(url, { headers: { 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content }, ...options });
            const data = await res.json();
            if (!res.ok && res.status === 422 && data.errors) { const f = Object.values(data.errors)[0]; throw new Error(Array.isArray(f)?f[0]:f); }
            if (!res.ok) throw new Error(data.message || 'Request failed');
            return data;
        },

        _toast(message, type = 'success') {
            const container = document.getElementById('admin-toast-container');
            if (!container) return;
            const el = document.createElement('div');
            el.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border text-xs font-medium transition-all ${type==='success'?'bg-white border-emerald-100 text-emerald-800':'bg-white border-rose-100 text-rose-700'}`;
            el.innerHTML = `<span class="w-2 h-2 rounded-full flex-shrink-0 ${type==='success'?'bg-emerald-400':'bg-rose-400'}"></span><span>${message}</span>`;
            container.appendChild(el);
            setTimeout(() => el.remove(), 4000);
        },
    };
}
</script>
@endpush
