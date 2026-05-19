@php
    $tabName = $tab;
    $title = [
        'regions' => 'Regions',
        'districts' => 'Districts',
        'towns' => 'Towns / Cities',
    ][$tabName];
    $subtitle = [
        'regions' => 'Administrative regions of Ghana.',
        'districts' => 'Districts within each region.',
        'towns' => 'Towns and cities within each district.',
    ][$tabName];
    $searchPlaceholder = [
        'regions' => 'Search region name or code...',
        'districts' => 'Search district name or code...',
        'towns' => 'Search town, district, or region...',
    ][$tabName];
    $loading = [
        'regions' => 'loadingRegions',
        'districts' => 'loadingDistricts',
        'towns' => 'loadingTowns',
    ][$tabName];
    $searchModel = [
        'regions' => 'regionSearch',
        'districts' => 'districtSearch',
        'towns' => 'townSearch',
    ][$tabName];
    $filterModel = [
        'regions' => 'showRegionFilters',
        'districts' => 'showDistrictFilters',
        'towns' => 'showTownFilters',
    ][$tabName];
    $addType = $tabName;
    $empty = [
        'regions' => 'No regions match the current filters.',
        'districts' => 'No districts match the current filters.',
        'towns' => 'No towns match the current filters.',
    ][$tabName];
@endphp

<div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
    <div class="border-b border-slate-200/60 px-5 py-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0Z"/></svg>
                </div>
                <div class="min-w-0">
                    <h2 class="text-lg font-extrabold text-slate-900">{{ $title }}</h2>
                    <p class="truncate text-sm text-slate-500">{{ $subtitle }}</p>
                </div>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700" x-text="tableConfig('{{ $tabName }}').total() + ' total'"></span>
        </div>
    </div>

    <div class="border-b border-slate-100 px-5 py-4">
        <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="w-full xl:max-w-md">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/></svg>
                    <input type="text" x-model="{{ $searchModel }}" @input.debounce.500ms="{{ $tabName === 'towns' ? 'townStats.offset = 0; loadTowns()' : ($tabName === 'districts' ? 'districtPage = 1; loadDistricts()' : 'regionPage = 1; loadRegions()') }}" placeholder="{{ $searchPlaceholder }}" class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                <button type="button" @click="{{ $filterModel }} = !{{ $filterModel }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="{{ $filterModel }} ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    <span x-text="{{ $filterModel }} ? 'Hide Filters' : 'Filters'"></span>
                </button>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                        View
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                        <template x-for="col in tableConfig('{{ $tabName }}').columns ? this[tableConfig('{{ $tabName }}').columns] : []" :key="col.key">
                            <button type="button" @click="this[tableConfig('{{ $tabName }}').visible][col.key] = !this[tableConfig('{{ $tabName }}').visible][col.key]" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <span x-text="col.label"></span>
                                <svg x-show="this[tableConfig('{{ $tabName }}').visible][col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                        Export
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                        <button type="button" @click="exportCSV('{{ $tabName }}'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                        <div class="my-1 border-t border-slate-100"></div>
                        <button type="button" @click="printTable('{{ $tabName }}'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                    </div>
                </div>
                <button type="button" @click="openAddModal('{{ $addType }}')" class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>{{ ['regions' => 'Add Region', 'districts' => 'Add District', 'towns' => 'Add Town'][$tabName] }}</span>
                </button>
            </div>
        </div>

        <div x-show="{{ $filterModel }}" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
            @include('admin.locations.partials.filters', ['tab' => $tabName])
        </div>

        <div class="flex flex-wrap gap-2" x-show="tableConfig('{{ $tabName }}').chips().length">
            <template x-for="chip in tableConfig('{{ $tabName }}').chips()" :key="chip.key">
                <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                    <span x-text="chip.label"></span>
                    <button type="button" @click="tableConfig('{{ $tabName }}').clearChip(chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                </span>
            </template>
        </div>
    </div>

    <div class="relative overflow-hidden">
        <div x-show="{{ $loading }}" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
        <div class="hidden overflow-x-auto lg:block">
            @include('admin.locations.partials.table', ['tab' => $tabName])
        </div>
        <div class="divide-y divide-slate-100 lg:hidden">
            <template x-if="tableConfig('{{ $tabName }}').rows().length === 0 && !{{ $loading }}">
                <div class="px-4 py-12 text-center text-sm text-slate-400">{{ $empty }}</div>
            </template>
            <template x-for="item in tableConfig('{{ $tabName }}').rows()" :key="item.id">
                @include('admin.locations.partials.mobile-card', ['tab' => $tabName])
            </template>
        </div>
        <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs font-semibold text-slate-600">
                    Showing <span x-text="tableConfig('{{ $tabName }}').from()"></span> to <span x-text="tableConfig('{{ $tabName }}').to()"></span> of <span x-text="tableConfig('{{ $tabName }}').total()"></span>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-end">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-600">Rows</span>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700">
                                <span x-text="this[tableConfig('{{ $tabName }}').perPage]"></span>
                                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" style="display:none">
                                <template x-for="n in [10,25,50,100]" :key="n"><button type="button" @click="tableConfig('{{ $tabName }}').setPerPage(n); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="this[tableConfig('{{ $tabName }}').perPage] == n ? 'bg-slate-100' : ''" x-text="n"></button></template>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="tableConfig('{{ $tabName }}').prev()" :disabled="tableConfig('{{ $tabName }}').page() <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                        <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="tableConfig('{{ $tabName }}').page()"></span> / <span x-text="tableConfig('{{ $tabName }}').last()"></span></div>
                        <button @click="tableConfig('{{ $tabName }}').next()" :disabled="tableConfig('{{ $tabName }}').page() >= tableConfig('{{ $tabName }}').last()" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
