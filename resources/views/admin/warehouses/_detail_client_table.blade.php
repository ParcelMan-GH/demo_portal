@php
    $title = $title ?? 'Records';
    $subtitle = $subtitle ?? '';
    $noun = $noun ?? 'records';
    $emptyTitle = $emptyTitle ?? 'No records found.';
@endphp

<section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
    <div class="border-b border-slate-200/60 px-5 py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h2 class="text-lg font-extrabold text-slate-900">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-0.5 text-sm font-medium text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            <span class="inline-flex h-9 w-fit items-center rounded-full bg-slate-100 px-3 text-xs font-black text-slate-700">
                <span x-text="meta.total"></span>&nbsp;{{ $noun }}
            </span>
        </div>
    </div>

    <div class="border-b border-slate-100 px-5 py-4">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="w-full xl:max-w-md">
                <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="search" placeholder="Search {{ strtolower($title) }}..."
                        class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                <button type="button" x-show="hasStatusFilter() || hasDirectionFilter()" @@click="showFilters = !showFilters"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                    </svg>
                    <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                </button>

                @include('admin.warehouses._inventory_view_btn')
                @include('admin.warehouses._inventory_export_btn')
            </div>
        </div>

        <div x-show="showFilters" x-transition class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div x-show="hasStatusFilter()">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                    <select x-model="statusFilter" @@change="currentPage = 1; recompute()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">All statuses</option>
                        <template x-for="option in statusOptions()" :key="option">
                            <option :value="option" x-text="option"></option>
                        </template>
                    </select>
                </div>
                <div x-show="hasDirectionFilter()">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Direction</label>
                    <select x-model="directionFilter" @@change="currentPage = 1; recompute()" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">All directions</option>
                        <template x-for="option in directionOptions()" :key="option">
                            <option :value="option" x-text="option"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2" x-show="activeFilterChips().length">
            <template x-for="chip in activeFilterChips()" :key="chip.key">
                <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                    <span x-text="chip.label"></span>
                    <button type="button" @@click="clearFilter(chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                </span>
            </template>
        </div>
    </div>

    <div class="relative overflow-hidden bg-white">
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full min-w-[980px] divide-y divide-slate-200/60 text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <template x-for="col in columns" :key="col.key">
                            <th x-show="columnVisible(col.key)" @@click="col.key !== 'actions' && sort(col.key)"
                                class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500"
                                :class="{'cursor-pointer': col.key !== 'actions', 'text-center': col.align === 'center', 'text-right': col.align === 'right' || col.key === 'actions'}">
                                <span x-text="col.label"></span>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <template x-if="items.length === 0">
                        <tr>
                            <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">{{ $emptyTitle }}</td>
                        </tr>
                    </template>
                    <template x-for="row in items" :key="row.id">
                        <tr class="hover:bg-slate-50/70">
                            <template x-for="col in columns" :key="col.key">
                                <td x-show="columnVisible(col.key)" class="px-4 py-3 align-middle" :class="{'text-center': col.align === 'center', 'text-right': col.align === 'right' || col.key === 'actions'}">
                                    <template x-if="col.type === 'status'">
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-black" :class="row[col.classKey] || row[col.key + '_class'] || 'bg-slate-100 text-slate-700'" x-text="row[col.key] || '-'"></span>
                                    </template>
                                    <template x-if="col.type === 'link'">
                                        <a :href="row[col.hrefKey] || '#'" class="font-mono text-xs font-black text-orange-700 underline decoration-orange-200 underline-offset-4 hover:text-orange-800" x-text="row[col.key] || '-'"></a>
                                    </template>
                                    <template x-if="col.type === 'action'">
                                        <a :href="row.view_url || '#'" class="inline-flex rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100">Open</a>
                                    </template>
                                    <template x-if="!['status','link','action'].includes(col.type || '')">
                                        <span class="text-xs font-semibold text-slate-700" x-text="row[col.key] || '-'"></span>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 lg:hidden">
            <template x-if="items.length === 0">
                <div class="px-4 py-10 text-center text-sm font-semibold text-slate-400">{{ $emptyTitle }}</div>
            </template>
            <template x-for="row in items" :key="row.id">
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="break-words text-sm font-black text-slate-900" x-text="primaryLine(row)"></p>
                            <p class="mt-1 break-words text-xs font-semibold text-slate-500" x-text="secondaryLine(row)"></p>
                        </div>
                        <template x-if="row.status || row.status_label">
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black" :class="row.status_badge_class || 'bg-slate-100 text-slate-700'" x-text="row.status || row.status_label"></span>
                        </template>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                        <template x-for="field in mobileFields(row)" :key="field.label">
                            <div>
                                <p class="font-black uppercase tracking-wide text-slate-400" x-text="field.label"></p>
                                <p class="mt-1 font-bold text-slate-800" x-text="field.value"></p>
                            </div>
                        </template>
                    </div>
                    <a :href="row.view_url || '#'" class="mt-4 inline-flex rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700">Open</a>
                </article>
            </template>
        </div>

        @include('admin.warehouses._inventory_pagination', ['noun' => $noun])
    </div>
</section>
