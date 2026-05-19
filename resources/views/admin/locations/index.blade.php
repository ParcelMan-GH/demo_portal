@extends('admin.layouts.app')

@section('title', 'Location Management')
@section('breadcrumb-parent', 'System')
@section('breadcrumb-current', 'Location Management')

@section('content')
<div x-data="locationManager()" x-init="init()" class="space-y-5">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
        <button type="button" @click="switchTab('regions')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M21 12a9 9 0 1 1-9-9m9 9H3m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Regions</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="regionStats.total || 0"></p>
            </div>
        </button>
        <button type="button" @click="switchTab('districts')" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13 6-3m-6 3V7m6 10 4.553 2.276A1 1 0 0 0 21 18.382V7.618a1 1 0 0 0-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Districts</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="districtStats.total || 0"></p>
            </div>
        </button>
        <button type="button" @click="switchTab('towns')" class="col-span-2 flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-3 py-3 text-left shadow-sm transition hover:border-orange-200 hover:bg-orange-50/30 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-orange-100 lg:col-span-1">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-[9px] font-black uppercase tracking-wide text-slate-400">Towns / Cities</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="townStats.total || 0"></p>
            </div>
        </button>
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="switchTab('regions')" class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition" :class="activeTab === 'regions' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 1 1-9-9m9 9H3"/></svg>
                Regions
            </button>
            <button type="button" @click="switchTab('districts')" class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition" :class="activeTab === 'districts' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13 6-3m-6 3V7"/></svg>
                Districts
            </button>
            <button type="button" @click="switchTab('towns')" class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition" :class="activeTab === 'towns' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0Z"/></svg>
                Towns / Cities
            </button>
        </div>
    </section>

    <template x-if="activeTab === 'regions'">
        <section>@include('admin.locations.partials.table-shell', ['tab' => 'regions'])</section>
    </template>
    <template x-if="activeTab === 'districts'">
        <section>@include('admin.locations.partials.table-shell', ['tab' => 'districts'])</section>
    </template>
    <template x-if="activeTab === 'towns'">
        <section>@include('admin.locations.partials.table-shell', ['tab' => 'towns'])</section>
    </template>

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @keydown.escape.window="closeModal()">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div @click.stop class="relative w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0Z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900" x-text="modalTitle"></h3>
                                <p class="mt-1 text-sm font-semibold text-slate-500" x-text="modalSubtitle()"></p>
                            </div>
                        </div>
                        <button type="button" @click="closeModal()" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="max-h-[calc(100vh-240px)] space-y-5 overflow-y-auto px-6 py-6">
                    <div x-show="modalError" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-700" x-text="modalError"></div>
                    <template x-if="modalType === 'regions'">
                        <div class="space-y-5">
                            <x-admin.locations.form-input label="Region Name" model="form.name" placeholder="e.g. Greater Accra" required="true" />
                            <x-admin.locations.form-input label="Code" model="form.code" placeholder="e.g. GAR" required="true" class-name="uppercase" help="Short unique code used internally." />
                        </div>
                    </template>
                    <template x-if="modalType === 'districts'">
                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Region <span class="text-rose-500">*</span></label>
                                <select x-model="form.region_id" @change="loadModalDistricts()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">Select region...</option>
                                    <template x-for="r in regions" :key="r.id"><option :value="r.id" x-text="r.name"></option></template>
                                </select>
                            </div>
                            <x-admin.locations.form-input label="District Name" model="form.name" placeholder="e.g. Accra Metropolitan" required="true" />
                            <x-admin.locations.form-input label="Code" model="form.code" placeholder="e.g. GAR-AMA" required="true" class-name="uppercase" />
                        </div>
                    </template>
                    <template x-if="modalType === 'towns'">
                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Region <span class="text-rose-500">*</span></label>
                                <select x-model="form.region_id" @change="loadModalDistricts()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">Select region...</option>
                                    <template x-for="r in regions" :key="r.id"><option :value="r.id" x-text="r.name"></option></template>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">District <span class="text-rose-500">*</span></label>
                                <select x-model="form.district_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="">Select district...</option>
                                    <template x-for="d in modalDistricts" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                                </select>
                            </div>
                            <x-admin.locations.form-input label="Town / City Name" model="form.name" placeholder="e.g. Kasoa" required="true" />
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Type <span class="text-rose-500">*</span></label>
                                <select x-model="form.type" class="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    <option value="town">Town</option>
                                    <option value="city">City</option>
                                    <option value="suburb">Suburb</option>
                                    <option value="village">Village</option>
                                </select>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-5">
                    <p class="text-xs text-slate-500"><span class="text-rose-500">*</span> Required fields</p>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="closeModal()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">Cancel</button>
                        <button type="button" @click="saveModal()" :disabled="saving" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:opacity-50">
                            <svg x-show="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="saving ? 'Saving...' : (editingId ? 'Save Changes' : 'Create')"></span>
                        </button>
                    </div>
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
        regions: [], districts: [], towns: [],
        loadingRegions: false, loadingDistricts: false, loadingTowns: false,
        regionStats: { total: 0 }, districtStats: { total: 0 }, townStats: { total: 0, limit: 50, offset: 0 },
        regionSearch: '', districtSearch: '', townSearch: '',
        regionPage: 1, districtPage: 1, regionPerPage: 25, districtPerPage: 25, townPerPage: 50,
        showRegionFilters: false, showDistrictFilters: false, showTownFilters: false,
        regionStatusFilter: '', regionCodeFilter: '', regionCoverageFilter: '', districtStatusFilter: '', townStatusFilter: '',
        regionDistrictsMin: '', regionDistrictsMax: '', regionTownsMin: '', regionTownsMax: '',
        districtRegionFilter: '', districtCodeFilter: '', districtRegionStatusFilter: '', districtCoverageFilter: '', districtTownsMin: '', districtTownsMax: '',
        townRegionFilter: '', townDistrictFilter: '', townTypeFilter: '', townRegionStatusFilter: '', townDistrictStatusFilter: '', townParentStateFilter: '', townFilterDistricts: [],
        regionSortBy: 'name', regionSortDir: 'asc', districtSortBy: 'name', districtSortDir: 'asc', townSortBy: 'name', townSortDir: 'asc',
        regionColumns: [{key:'name',label:'Region'},{key:'code',label:'Code'},{key:'districts',label:'Districts'},{key:'towns',label:'Towns'},{key:'status',label:'Status'}],
        districtColumns: [{key:'name',label:'District'},{key:'code',label:'Code'},{key:'region',label:'Region'},{key:'towns',label:'Towns'},{key:'status',label:'Status'}],
        townColumns: [{key:'name',label:'Town / City'},{key:'type',label:'Type'},{key:'district',label:'District'},{key:'region',label:'Region'},{key:'status',label:'Status'}],
        regionVisible: { name:true, code:true, districts:true, towns:true, status:true },
        districtVisible: { name:true, code:true, region:true, towns:true, status:true },
        townVisible: { name:true, type:true, district:true, region:true, status:true },
        modalOpen: false, modalType: '', modalTitle: '', editingId: null, modalDistricts: [], modalError: '', saving: false,
        form: { name: '', code: '', region_id: '', district_id: '', type: 'town' },

        async init() { await this.loadRegions(); await this.loadDistricts(); await this.loadTowns(); },
        switchTab(tab) { this.activeTab = tab; },
        modalSubtitle() { return this.modalType === 'regions' ? 'Manage region name and code.' : this.modalType === 'districts' ? 'Assign district to a region and set its code.' : 'Place this town or city under the right district.'; },

        sortR(key) { this.regionSortDir = this.regionSortBy === key ? (this.regionSortDir === 'asc' ? 'desc' : 'asc') : 'asc'; this.regionSortBy = key; this.regionPage = 1; },
        sortD(key) { this.districtSortDir = this.districtSortBy === key ? (this.districtSortDir === 'asc' ? 'desc' : 'asc') : 'asc'; this.districtSortBy = key; this.districtPage = 1; },
        sortT(key) { this.townSortDir = this.townSortBy === key ? (this.townSortDir === 'asc' ? 'desc' : 'asc') : 'asc'; this.townSortBy = key; },
        _sortArr(arr, key, dir) { return [...arr].sort((a, b) => { const av = a[key] ?? '', bv = b[key] ?? ''; const cmp = typeof av === 'number' ? av - bv : String(av).localeCompare(String(bv)); return dir === 'asc' ? cmp : -cmp; }); },
        _inRange(value, min, max) { const minN = min === '' ? null : Number(min); const maxN = max === '' ? null : Number(max); return (minN === null || value >= minN) && (maxN === null || value <= maxN); },

        getSortedRegions() { let arr = this.regionStatusFilter ? this.regions.filter(r => this.regionStatusFilter === 'active' ? r.is_active : !r.is_active) : this.regions; arr = arr.filter(r => this._inRange(Number(r.districts_count || 0), this.regionDistrictsMin, this.regionDistrictsMax)); arr = arr.filter(r => this._inRange(Number(r.locations_count || 0), this.regionTownsMin, this.regionTownsMax)); return this._sortArr(arr, this.regionSortBy, this.regionSortDir); },
        getSortedDistricts() { let arr = this.districtStatusFilter ? this.districts.filter(d => this.districtStatusFilter === 'active' ? d.is_active : !d.is_active) : this.districts; arr = arr.filter(d => this._inRange(Number(d.locations_count || 0), this.districtTownsMin, this.districtTownsMax)); return this._sortArr(arr, this.districtSortBy, this.districtSortDir); },
        getSortedTowns() { return this._sortArr(this.towns, this.townSortBy, this.townSortDir); },

        getPagedRegions() { return this.getSortedRegions().slice((this.regionPage - 1) * this.regionPerPage, this.regionPage * this.regionPerPage); },
        getPagedDistricts() { return this.getSortedDistricts().slice((this.districtPage - 1) * this.districtPerPage, this.districtPage * this.districtPerPage); },
        regionLastPage() { return Math.max(1, Math.ceil(this.getSortedRegions().length / this.regionPerPage)); },
        districtLastPage() { return Math.max(1, Math.ceil(this.getSortedDistricts().length / this.districtPerPage)); },
        townCurrentPage() { return Math.floor(this.townStats.offset / this.townPerPage) + 1; },
        townLastPage() { return Math.max(1, Math.ceil(this.townStats.total / this.townPerPage)); },
        regionPageFrom() { return this.getSortedRegions().length ? (this.regionPage - 1) * this.regionPerPage + 1 : 0; },
        regionPageTo() { return Math.min(this.regionPage * this.regionPerPage, this.getSortedRegions().length); },
        districtPageFrom() { return this.getSortedDistricts().length ? (this.districtPage - 1) * this.districtPerPage + 1 : 0; },
        districtPageTo() { return Math.min(this.districtPage * this.districtPerPage, this.getSortedDistricts().length); },
        regionPrevPage() { if (this.regionPage > 1) this.regionPage--; }, regionNextPage() { if (this.regionPage < this.regionLastPage()) this.regionPage++; },
        districtPrevPage() { if (this.districtPage > 1) this.districtPage--; }, districtNextPage() { if (this.districtPage < this.districtLastPage()) this.districtPage++; },
        townPrevPageNav() { this.townStats.offset = Math.max(0, this.townStats.offset - this.townPerPage); this.loadTowns(); },
        townNextPageNav() { if (this.townCurrentPage() < this.townLastPage()) { this.townStats.offset += this.townPerPage; this.loadTowns(); } },
        setRegionPerPage(n) { this.regionPerPage = n; this.regionPage = 1; }, setDistrictPerPage(n) { this.districtPerPage = n; this.districtPage = 1; }, setTownPerPage(n) { this.townPerPage = n; this.townStats.limit = n; this.townStats.offset = 0; this.loadTowns(); },

        async loadRegions() { this.loadingRegions = true; try { const p = new URLSearchParams(); if (this.regionSearch) p.set('search', this.regionSearch); if (this.regionStatusFilter) p.set('active', this.regionStatusFilter === 'active' ? '1' : '0'); if (this.regionCodeFilter) p.set('code', this.regionCodeFilter); if (this.regionCoverageFilter) p.set('coverage', this.regionCoverageFilter); const res = await this._fetch(`/admin/locations-data/regions?${p}`); this.regions = res.data.regions; this.regionStats.total = res.data.total; } finally { this.loadingRegions = false; } },
        async loadDistricts() { this.loadingDistricts = true; try { const p = new URLSearchParams(); if (this.districtSearch) p.set('search', this.districtSearch); if (this.districtRegionFilter) p.set('region_id', this.districtRegionFilter); if (this.districtStatusFilter) p.set('active', this.districtStatusFilter === 'active' ? '1' : '0'); if (this.districtCodeFilter) p.set('code', this.districtCodeFilter); if (this.districtRegionStatusFilter) p.set('region_active', this.districtRegionStatusFilter === 'active' ? '1' : '0'); if (this.districtCoverageFilter) p.set('coverage', this.districtCoverageFilter); const res = await this._fetch(`/admin/locations-data/districts?${p}`); this.districts = res.data.districts; this.districtStats.total = res.data.total; } finally { this.loadingDistricts = false; } },
        async loadTowns() { this.loadingTowns = true; try { const p = new URLSearchParams(); if (this.townSearch) p.set('search', this.townSearch); if (this.townRegionFilter) p.set('region_id', this.townRegionFilter); if (this.townDistrictFilter) p.set('district_id', this.townDistrictFilter); if (this.townTypeFilter) p.set('type', this.townTypeFilter); if (this.townStatusFilter) p.set('active', this.townStatusFilter === 'active' ? '1' : '0'); if (this.townRegionStatusFilter) p.set('region_active', this.townRegionStatusFilter === 'active' ? '1' : '0'); if (this.townDistrictStatusFilter) p.set('district_active', this.townDistrictStatusFilter === 'active' ? '1' : '0'); if (this.townParentStateFilter) p.set('parent_state', this.townParentStateFilter); p.set('limit', this.townPerPage); p.set('offset', this.townStats.offset); const res = await this._fetch(`/admin/locations-data/towns?${p}`); this.towns = res.data.towns; this.townStats = { total: res.data.total, limit: res.data.limit, offset: res.data.offset }; } finally { this.loadingTowns = false; } },
        async loadTownDistricts() { if (!this.townRegionFilter) { this.townFilterDistricts = []; return; } const res = await this._fetch(`/admin/locations-data/districts?region_id=${this.townRegionFilter}`); this.townFilterDistricts = res.data.districts; },
        async loadModalDistricts() { if (!this.form.region_id) { this.modalDistricts = []; return; } const res = await this._fetch(`/admin/locations-data/districts?region_id=${this.form.region_id}`); this.modalDistricts = res.data.districts; },

        applyRegionFilters() { this.regionPage = 1; this.loadRegions(); }, applyDistrictFilters() { this.districtPage = 1; this.loadDistricts(); }, applyTownFilters() { this.townStats.offset = 0; this.loadTowns(); },
        clearRegionFilters() { this.regionStatusFilter = ''; this.regionCodeFilter = ''; this.regionCoverageFilter = ''; this.regionDistrictsMin = ''; this.regionDistrictsMax = ''; this.regionTownsMin = ''; this.regionTownsMax = ''; this.applyRegionFilters(); },
        clearDistrictFilters() { this.districtRegionFilter = ''; this.districtStatusFilter = ''; this.districtCodeFilter = ''; this.districtRegionStatusFilter = ''; this.districtCoverageFilter = ''; this.districtTownsMin = ''; this.districtTownsMax = ''; this.applyDistrictFilters(); },
        clearTownFilters() { this.townRegionFilter = ''; this.townDistrictFilter = ''; this.townTypeFilter = ''; this.townStatusFilter = ''; this.townRegionStatusFilter = ''; this.townDistrictStatusFilter = ''; this.townParentStateFilter = ''; this.townFilterDistricts = []; this.applyTownFilters(); },
        clearRegionFilter(key) { if (key === 'status') this.regionStatusFilter = ''; if (key === 'code') this.regionCodeFilter = ''; if (key === 'coverage') this.regionCoverageFilter = ''; if (key === 'districts') { this.regionDistrictsMin = ''; this.regionDistrictsMax = ''; } if (key === 'towns') { this.regionTownsMin = ''; this.regionTownsMax = ''; } this.applyRegionFilters(); },
        clearDistrictFilter(key) { if (key === 'region') this.districtRegionFilter = ''; if (key === 'status') this.districtStatusFilter = ''; if (key === 'code') this.districtCodeFilter = ''; if (key === 'region_status') this.districtRegionStatusFilter = ''; if (key === 'coverage') this.districtCoverageFilter = ''; if (key === 'towns') { this.districtTownsMin = ''; this.districtTownsMax = ''; } this.applyDistrictFilters(); },
        clearTownFilter(key) { if (key === 'region') { this.townRegionFilter = ''; this.townDistrictFilter = ''; this.townFilterDistricts = []; } if (key === 'district') this.townDistrictFilter = ''; if (key === 'type') this.townTypeFilter = ''; if (key === 'status') this.townStatusFilter = ''; if (key === 'region_status') this.townRegionStatusFilter = ''; if (key === 'district_status') this.townDistrictStatusFilter = ''; if (key === 'parent_state') this.townParentStateFilter = ''; this.applyTownFilters(); },
        _filterLabel(value) { return String(value || '').split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' '); },
        activeRegionFilterChips() { const chips = []; if (this.regionStatusFilter) chips.push({key:'status', label:this.regionStatusFilter === 'active' ? 'Active' : 'Inactive'}); if (this.regionCodeFilter) chips.push({key:'code', label:`Code: ${this.regionCodeFilter}`}); if (this.regionCoverageFilter) chips.push({key:'coverage', label:this._filterLabel(this.regionCoverageFilter)}); if (this.regionDistrictsMin || this.regionDistrictsMax) chips.push({key:'districts', label:`Districts ${this.regionDistrictsMin || 0}-${this.regionDistrictsMax || 'any'}`}); if (this.regionTownsMin || this.regionTownsMax) chips.push({key:'towns', label:`Towns ${this.regionTownsMin || 0}-${this.regionTownsMax || 'any'}`}); return chips; },
        activeDistrictFilterChips() { const chips = []; if (this.districtRegionFilter) chips.push({key:'region', label:(this.regions.find(r => String(r.id) === String(this.districtRegionFilter)) || {name:'Region'}).name}); if (this.districtStatusFilter) chips.push({key:'status', label:this.districtStatusFilter === 'active' ? 'Active' : 'Inactive'}); if (this.districtCodeFilter) chips.push({key:'code', label:`Code: ${this.districtCodeFilter}`}); if (this.districtRegionStatusFilter) chips.push({key:'region_status', label:this.districtRegionStatusFilter === 'active' ? 'Active region' : 'Inactive region'}); if (this.districtCoverageFilter) chips.push({key:'coverage', label:this._filterLabel(this.districtCoverageFilter)}); if (this.districtTownsMin || this.districtTownsMax) chips.push({key:'towns', label:`Towns ${this.districtTownsMin || 0}-${this.districtTownsMax || 'any'}`}); return chips; },
        activeTownFilterChips() { const chips = []; if (this.townRegionFilter) chips.push({key:'region', label:(this.regions.find(r => String(r.id) === String(this.townRegionFilter)) || {name:'Region'}).name}); if (this.townDistrictFilter) chips.push({key:'district', label:(this.townFilterDistricts.find(d => String(d.id) === String(this.townDistrictFilter)) || {name:'District'}).name}); if (this.townTypeFilter) chips.push({key:'type', label:this.townTypeFilter.charAt(0).toUpperCase() + this.townTypeFilter.slice(1)}); if (this.townStatusFilter) chips.push({key:'status', label:this.townStatusFilter === 'active' ? 'Active' : 'Inactive'}); if (this.townRegionStatusFilter) chips.push({key:'region_status', label:this.townRegionStatusFilter === 'active' ? 'Active region' : 'Inactive region'}); if (this.townDistrictStatusFilter) chips.push({key:'district_status', label:this.townDistrictStatusFilter === 'active' ? 'Active district' : 'Inactive district'}); if (this.townParentStateFilter) chips.push({key:'parent_state', label:this._filterLabel(this.townParentStateFilter)}); return chips; },

        filterDistricts(region) { this.districtRegionFilter = String(region.id); this.switchTab('districts'); this.applyDistrictFilters(); },
        filterTowns(district) { this.townRegionFilter = String(district.region_id); this.townDistrictFilter = String(district.id); this.switchTab('towns'); this.loadTownDistricts(); this.applyTownFilters(); },
        openAddModal(type) { this.modalType = type; this.editingId = null; this.modalError = ''; this.form = { name: '', code: '', region_id: '', district_id: '', type: 'town' }; this.modalDistricts = []; this.modalTitle = type === 'regions' ? 'Add New Region' : type === 'districts' ? 'Add New District' : 'Add New Town'; this.modalOpen = true; },
        openEditModal(type, item) { this.modalType = type; this.editingId = item.id; this.modalError = ''; this.modalDistricts = []; this.modalTitle = type === 'regions' ? 'Edit Region' : type === 'districts' ? 'Edit District' : 'Edit Town'; if (type === 'regions') this.form = { name: item.name, code: item.code, region_id: '', district_id: '', type: 'town' }; else if (type === 'districts') { this.form = { name: item.name, code: item.code, region_id: String(item.region_id), district_id: '', type: 'town' }; this.loadModalDistricts(); } else { this.form = { name: item.name, code: '', region_id: String(item.region_id), district_id: String(item.district_id), type: item.type }; this.loadModalDistricts(); } this.modalOpen = true; },
        closeModal() { this.modalOpen = false; this.modalError = ''; },
        async saveModal() { this.saving = true; this.modalError = ''; try { const seg = this.modalType === 'regions' ? 'regions' : this.modalType === 'districts' ? 'districts' : 'towns'; const url = this.editingId ? `/admin/locations/${seg}/${this.editingId}` : `/admin/locations/${seg}`; const payload = this.modalType === 'regions' ? { name: this.form.name, code: this.form.code } : this.modalType === 'districts' ? { name: this.form.name, code: this.form.code, region_id: this.form.region_id } : { name: this.form.name, type: this.form.type, district_id: this.form.district_id }; const res = await this._fetch(url, { method: this.editingId ? 'PUT' : 'POST', body: JSON.stringify(payload) }); if (!res.success) { this.modalError = res.message || 'Failed to save.'; return; } this.closeModal(); this._toast(res.message, 'success'); if (this.modalType === 'regions') this.loadRegions(); else if (this.modalType === 'districts') { this.loadDistricts(); this.loadRegions(); } else { this.loadTowns(); this.loadDistricts(); } } catch (e) { this.modalError = e.message || 'An error occurred.'; } finally { this.saving = false; } },
        async toggleItem(type, item) { const seg = type === 'regions' ? 'regions' : type === 'districts' ? 'districts' : 'towns'; const res = await this._fetch(`/admin/locations/${seg}/${item.id}/toggle`, { method: 'PATCH' }); if (res.success) { item.is_active = res.data.is_active; this._toast(res.message, 'success'); } else this._toast(res.message || 'Failed.', 'error'); },
        async deleteItem(type, item) { if (!confirm(`Delete \"${item.name}\"? This cannot be undone.`)) return; const seg = type === 'regions' ? 'regions' : type === 'districts' ? 'districts' : 'towns'; const res = await this._fetch(`/admin/locations/${seg}/${item.id}`, { method: 'DELETE' }); if (res.success) { this._toast(res.message, 'success'); if (type === 'regions') this.loadRegions(); else if (type === 'districts') { this.loadDistricts(); this.loadRegions(); } else { this.loadTowns(); this.loadDistricts(); } } else this._toast(res.message || 'Cannot delete.', 'error'); },
        exportCSV(tab) { const cfg = this.tableConfig(tab); const rows = cfg.exportRows(); const csv = [cfg.exportHeaders.join(','), ...rows.map(r => r.map(c => `\"${String(c ?? '').replace(/\"/g, '\"\"')}\"`).join(','))].join('\\n'); const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' })); a.download = `${tab}.csv`; document.body.appendChild(a); a.click(); document.body.removeChild(a); },
        printTable(tab) { const cfg = this.tableConfig(tab); const rows = cfg.exportRows(); const w = window.open('', '_blank'); if (!w) return alert('Pop-up blocked.'); w.document.body.innerHTML = `<style>body{font-family:system-ui,sans-serif;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #e2e8f0;padding:8px 12px;text-align:left;font-size:11px}th{background:#f1f5f9}</style><h1>${cfg.title}</h1><table><thead><tr>${cfg.exportHeaders.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>${rows.map(r => `<tr>${r.map(c => `<td>${c ?? '-'}</td>`).join('')}</tr>`).join('')}</tbody></table>`; setTimeout(() => w.print(), 250); },
        tableConfig(tab) {
            const map = {
                regions: { title:'Regions', icon:'region', loading:'loadingRegions', search:'regionSearch', showFilters:'showRegionFilters', columns:'regionColumns', visible:'regionVisible', rows:() => this.getPagedRegions(), total:() => this.getSortedRegions().length, from:() => this.regionPageFrom(), to:() => this.regionPageTo(), page:() => this.regionPage, last:() => this.regionLastPage(), perPage:'regionPerPage', setPerPage:(n)=>this.setRegionPerPage(n), prev:()=>this.regionPrevPage(), next:()=>this.regionNextPage(), add:()=>this.openAddModal('regions'), addLabel:'Add Region', sort:(k)=>this.sortR(k), sortBy:()=>this.regionSortBy, filters:'showRegionFilters', chips:()=>this.activeRegionFilterChips(), clearChip:(k)=>this.clearRegionFilter(k), exportHeaders:['Name','Code','Districts','Towns','Status'], exportRows:()=>this.getSortedRegions().map(r=>[r.name,r.code,r.districts_count,r.locations_count,r.is_active?'Active':'Inactive']) },
                districts: { title:'Districts', icon:'district', loading:'loadingDistricts', search:'districtSearch', showFilters:'showDistrictFilters', columns:'districtColumns', visible:'districtVisible', rows:() => this.getPagedDistricts(), total:() => this.getSortedDistricts().length, from:() => this.districtPageFrom(), to:() => this.districtPageTo(), page:() => this.districtPage, last:() => this.districtLastPage(), perPage:'districtPerPage', setPerPage:(n)=>this.setDistrictPerPage(n), prev:()=>this.districtPrevPage(), next:()=>this.districtNextPage(), add:()=>this.openAddModal('districts'), addLabel:'Add District', sort:(k)=>this.sortD(k), sortBy:()=>this.districtSortBy, filters:'showDistrictFilters', chips:()=>this.activeDistrictFilterChips(), clearChip:(k)=>this.clearDistrictFilter(k), exportHeaders:['Name','Code','Region','Towns','Status'], exportRows:()=>this.getSortedDistricts().map(d=>[d.name,d.code,d.region_name,d.locations_count,d.is_active?'Active':'Inactive']) },
                towns: { title:'Towns / Cities', icon:'town', loading:'loadingTowns', search:'townSearch', showFilters:'showTownFilters', columns:'townColumns', visible:'townVisible', rows:() => this.getSortedTowns(), total:() => this.townStats.total, from:() => this.townStats.total ? this.townStats.offset + 1 : 0, to:() => Math.min(this.townStats.offset + this.townPerPage, this.townStats.total), page:() => this.townCurrentPage(), last:() => this.townLastPage(), perPage:'townPerPage', setPerPage:(n)=>this.setTownPerPage(n), prev:()=>this.townPrevPageNav(), next:()=>this.townNextPageNav(), add:()=>this.openAddModal('towns'), addLabel:'Add Town', sort:(k)=>this.sortT(k), sortBy:()=>this.townSortBy, filters:'showTownFilters', chips:()=>this.activeTownFilterChips(), clearChip:(k)=>this.clearTownFilter(k), exportHeaders:['Name','Type','District','Region','Status'], exportRows:()=>this.getSortedTowns().map(t=>[t.name,t.type,t.district_name,t.region_name,t.is_active?'Active':'Inactive']) },
            };
            return map[tab];
        },
        async _fetch(url, options = {}) { const res = await fetch(url, { headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name=\"csrf-token\"]').content }, ...options }); const data = await res.json(); if (!res.ok && data.errors) { const first = Object.values(data.errors)[0]; throw new Error(Array.isArray(first) ? first[0] : first); } if (!res.ok) throw new Error(data.message || 'Request failed'); return data; },
        _toast(message, type = 'success') { const container = document.getElementById('admin-toast-container'); if (!container) return; const el = document.createElement('div'); el.className = `pointer-events-auto flex items-center gap-3 rounded-xl border bg-white px-4 py-3 text-xs font-medium shadow-lg ${type === 'success' ? 'border-emerald-100 text-emerald-800' : 'border-rose-100 text-rose-700'}`; el.innerHTML = `<span class=\"h-2 w-2 shrink-0 rounded-full ${type === 'success' ? 'bg-emerald-400' : 'bg-rose-400'}\"></span><span>${message}</span>`; container.appendChild(el); setTimeout(() => el.remove(), 4000); },
    };
}
</script>
@endpush
