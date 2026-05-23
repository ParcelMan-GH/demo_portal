@extends('warehouse.layouts.app')

@section('title', 'Pending Confirmations')

@section('content')
@php
    $pendingConfirmationsConfig = [
        'dataUrl' => route('warehouse.deliveries.pending-confirmations-data'),
        'confirmUrlTemplate' => route('warehouse.deliveries.runs.stops.confirm-handoff', ['run' => '__RUN__', 'stop' => '__STOP__']),
    ];
@endphp

<div
    class="space-y-5"
    x-data="pendingConfirmations()"
    x-init="init()"
    data-config='@json($pendingConfirmationsConfig)'
>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Pending</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.total || 0">0</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Needs Follow-up</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.needs_followup || 0">0</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">Under 24h</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="meta.pending_recent || 0">0</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7h-7m7 0v7m0-7-8 8-4-4-5 5"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase leading-snug tracking-[0.12em] text-slate-400">This Page</p>
                <p class="mt-1 text-xl font-extrabold text-slate-900" x-text="stops.length || 0">0</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-extrabold text-slate-900">Pending Confirmations</h2>
                        <p class="truncate text-sm text-slate-500">Bus courier handoffs waiting for recipient delivery confirmation.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full lg:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.400ms="currentPage = 1; fetchData()"
                            placeholder="Search..."
                            class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                        >
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" @@click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>

                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                            View
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white p-2 shadow-2xl" style="display: none;">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2Z"/></svg>
                            Export
                        </button>
                        <div x-show="open" x-cloak @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white p-2 shadow-2xl" style="display: none;">
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                            <button type="button" @@click="exportData('print'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
                    </div>

                    <button type="button" @@click="fetchData()" :disabled="loading" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50">
                        <svg class="h-4 w-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.64 18.36A9 9 0 0 0 18.36 5.64M18.36 5.64H14m4.36 0V10M5.64 18.36H10m-4.36 0V14"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Follow-up State</label>
                        <select x-model="filters.followup" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All pending</option>
                            <option value="needs_followup">Needs follow-up</option>
                            <option value="recent">Under 24h</option>
                        </select>
                    </div>
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Handoff Date</label>
                        <input
                            type="text"
                            x-ref="handoffDateRange"
                            placeholder="Select date range"
                            readonly
                            class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                        >
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Package Range</label>
                        <div class="flex overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                            <input type="number" min="0" x-model="filters.packages_min" placeholder="Min" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                            <div class="w-px bg-slate-200"></div>
                            <input type="number" min="0" x-model="filters.packages_max" placeholder="Max" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-sm font-semibold text-slate-900 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Confirmation</label>
                        <select x-model="filters.confirmation_status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All confirmation states</option>
                            <option value="pending">Pending</option>
                            <option value="code_sent">Code sent</option>
                            <option value="issue_reported">Issue reported</option>
                            <option value="confirmed">Rider/Public confirmed</option>
                            <option value="admin_confirmed">Admin confirmed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Source</label>
                        <select x-model="filters.confirmation_source" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All sources</option>
                            <option value="rider_code">Rider code</option>
                            <option value="public_link">Public link</option>
                            <option value="admin">Admin</option>
                            <option value="vendor_followup">Vendor follow-up</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full table-auto divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th x-show="visibleColumns.run_number" class="w-[13%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Run #</th>
                            <th x-show="visibleColumns.recipient" class="w-[19%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Recipient</th>
                            <th x-show="visibleColumns.destination" class="w-[18%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Destination</th>
                            <th x-show="visibleColumns.courier" class="w-[19%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Courier</th>
                            <th x-show="visibleColumns.handoff_at" class="w-[13%] px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Handed Off</th>
                            <th x-show="visibleColumns.packages" class="w-[8%] px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Packages</th>
                            <th x-show="visibleColumns.status" class="w-[10%] px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th x-show="visibleColumns.actions" class="px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/70 bg-transparent">
                        <template x-if="!loading && stops.length === 0">
                            <tr>
                                <td :colspan="visibleColumnCount()" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600">No pending confirmations</p>
                                        <p class="text-xs font-semibold text-slate-400">All bus courier handoffs have been confirmed.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="stop in stops" :key="stop.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td x-show="visibleColumns.run_number" class="whitespace-nowrap px-4 py-3">
                                        <a :href="stop.run_url" class="font-bold text-slate-900 hover:text-orange-700 hover:underline" x-text="stop.run_number || '-'"></a>
                                    </td>
                                    <td x-show="visibleColumns.recipient" class="px-4 py-3">
                                        <p class="font-semibold text-slate-900" x-text="stop.recipient_name || '-'"></p>
                                        <a :href="'tel:' + stop.recipient_phone" class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-orange-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 0 1 2-2h3.28a1 1 0 0 1 .948.684l1.498 4.493a1 1 0 0 1-.502 1.21l-2.257 1.13a11 11 0 0 0 5.516 5.516l1.13-2.257a1 1 0 0 1 1.21-.502l4.493 1.498a1 1 0 0 1 .684.949V19a2 2 0 0 1-2 2h-1C9.716 21 3 14.284 3 6V5Z"/></svg>
                                            <span x-text="stop.recipient_phone || '-'"></span>
                                        </a>
                                    </td>
                                    <td x-show="visibleColumns.destination" class="px-4 py-3">
                                        <p class="font-semibold text-slate-800" x-text="stop.destination_town || stop.town || '-'"></p>
                                        <p class="mt-1 text-[11px] text-slate-500" x-text="[stop.destination_district || stop.district, stop.region].filter(Boolean).join(', ') || '-'"></p>
                                    </td>
                                    <td x-show="visibleColumns.courier" class="px-4 py-3">
                                        <p class="font-semibold text-slate-900" x-text="stop.courier_name || '-'"></p>
                                        <a :href="'tel:' + stop.courier_phone" class="mt-1 block text-[11px] font-semibold text-slate-500 hover:text-orange-700" x-text="stop.courier_phone || '-'"></a>
                                        <p class="mt-1 font-mono text-[11px] text-slate-400" x-text="stop.vehicle_number || 'No vehicle'"></p>
                                    </td>
                                    <td x-show="visibleColumns.handoff_at" class="whitespace-nowrap px-4 py-3">
                                        <p class="font-semibold text-slate-700" x-text="stop.handed_off_at || stop.handoff_at || '-'"></p>
                                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-black" :class="stop.needs_followup ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-slate-100 text-slate-600'" x-text="hoursLabel(stop)"></span>
                                    </td>
                                    <td x-show="visibleColumns.packages" class="whitespace-nowrap px-3 py-3 text-center">
                                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-bold text-slate-700" x-text="stop.packages_count || stop.total_packages || 0"></span>
                                    </td>
                                    <td x-show="visibleColumns.status" class="whitespace-nowrap px-3 py-3 text-center">
                                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold" :class="stop.needs_followup ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-amber-200 bg-amber-50 text-amber-700'" x-text="stop.needs_followup ? 'Needs Follow-up' : 'Pending'"></span>
                                    </td>
                                    <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <button type="button" @@click="openActionModal(stop, 'delivered')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-100">Confirm Delivered</button>
                                            <button type="button" @@click="openActionModal(stop, 'failed')" class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-100">Mark Failed</button>
                                        </div>
                                    </td>
                                </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="!loading && stops.length === 0">
                    <div class="px-4 py-12 text-center">
                        <p class="text-sm font-bold text-slate-600">No pending confirmations</p>
                        <p class="mt-1 text-xs font-semibold text-slate-400">All bus courier handoffs have been confirmed.</p>
                    </div>
                </template>
                <template x-for="stop in stops" :key="stop.id">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a :href="stop.run_url" class="text-sm font-extrabold text-slate-900 hover:text-orange-700" x-text="stop.run_number || '-'"></a>
                                <p class="mt-1 text-xs font-bold text-slate-500" x-text="stop.recipient_name || '-'"></p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold" :class="stop.needs_followup ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-amber-200 bg-amber-50 text-amber-700'" x-text="stop.needs_followup ? 'Needs Follow-up' : 'Pending'"></span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Recipient</p><a :href="'tel:' + stop.recipient_phone" class="font-bold text-slate-800" x-text="stop.recipient_phone || '-'"></a></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Packages</p><p class="font-bold text-slate-800" x-text="stop.packages_count || stop.total_packages || 0"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Destination</p><p class="font-bold text-slate-800" x-text="stop.destination_town || stop.town || '-'"></p><p class="text-slate-500" x-text="stop.destination_district || stop.district || ''"></p></div>
                            <div><p class="font-black uppercase tracking-wide text-slate-400">Courier</p><p class="font-bold text-slate-800" x-text="stop.courier_name || '-'"></p><p class="text-slate-500" x-text="stop.courier_phone || ''"></p></div>
                            <div class="col-span-2"><p class="font-black uppercase tracking-wide text-slate-400">Handed Off</p><p class="font-bold text-slate-800" x-text="stop.handed_off_at || stop.handoff_at || '-'"></p><span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-600" x-text="hoursLabel(stop)"></span></div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" @@click="openActionModal(stop, 'delivered')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Confirm Delivered</button>
                            <button type="button" @@click="openActionModal(stop, 'failed')" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">Mark Failed</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">
                        Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button @@click="goToPage(currentPage - 1)" :disabled="currentPage <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div>
                        <button @@click="goToPage(currentPage + 1)" :disabled="currentPage >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div
            x-show="actionModalOpen"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[120] flex min-h-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4"
            style="display: none;"
            @@click.self="closeActionModal()"
            @@keydown.escape.window="closeActionModal()"
        >
            <div class="relative flex max-h-[calc(100dvh-3rem)] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl sm:max-h-[calc(100vh-2rem)]" @@click.stop>
                <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white shadow-lg"
                            :class="actionType === 'failed' ? 'bg-gradient-to-br from-rose-500 to-rose-700 shadow-rose-500/25' : 'bg-gradient-to-br from-emerald-500 to-emerald-700 shadow-emerald-500/25'"
                        >
                            <svg x-show="actionType !== 'failed'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                            </svg>
                            <svg x-show="actionType === 'failed'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-extrabold text-slate-900" x-text="actionType === 'failed' ? 'Mark Delivery Failed' : 'Confirm Delivery'"></h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500" x-text="actionType === 'failed' ? 'Record that the recipient did not receive this bus handoff.' : 'Confirm the recipient received this bus handoff.'"></p>
                        </div>
                    </div>
                    <button type="button" @@click="closeActionModal()" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50/70 p-5">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Recipient</p>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate text-base font-black text-slate-900" x-text="actionStop?.recipient_name || '-'"></p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-500" x-text="actionStop?.recipient_phone || '-'"></p>
                            </div>
                            <span class="shrink-0 rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700" x-text="(actionStop?.packages_count || actionStop?.total_packages || 0) + ' package(s)'"></span>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 text-sm sm:grid-cols-2">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Courier</p>
                                <p class="mt-0.5 font-bold text-slate-800" x-text="actionStop?.courier_name || '-'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Handed Off</p>
                                <p class="mt-0.5 font-bold text-slate-800" x-text="actionStop?.handed_off_at || actionStop?.handoff_at || '-'"></p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Notes <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <textarea
                            x-model="actionNotes"
                            rows="4"
                            placeholder="Add confirmation notes..."
                            class="w-full resize-none rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                        ></textarea>
                    </div>
                </div>

                <div class="shrink-0 flex justify-end gap-3 rounded-b-3xl border-t border-slate-100 bg-slate-50 p-4">
                    <button type="button" @@click="closeActionModal()" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                    <button
                        type="button"
                        @@click="submitAction(actionStop, actionType)"
                        :disabled="submitting || !actionStop"
                        class="rounded-xl border-2 px-5 py-3 text-base font-black text-white shadow-lg transition disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                        :class="actionType === 'failed' ? 'border-rose-600 bg-rose-600 shadow-rose-600/20 hover:border-rose-700 hover:bg-rose-700' : 'border-emerald-600 bg-emerald-600 shadow-emerald-600/20 hover:border-emerald-700 hover:bg-emerald-700'"
                        x-text="submitting ? 'Saving...' : (actionType === 'failed' ? 'Mark Failed' : 'Confirm Delivered')"
                    ></button>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function pendingConfirmations() {
    const config = JSON.parse(document.querySelector('[data-config]').getAttribute('data-config'));

    return {
        stops: [],
        loading: true,
        submitting: false,
        showFilters: false,
        search: '',
        currentPage: 1,
        meta: { total: 0, needs_followup: 0, pending_recent: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        actionModalOpen: false,
        actionType: 'delivered',
        actionStop: null,
        actionNotes: '',
        filters: {
            followup: '',
            handoff_date_from: '',
            handoff_date_to: '',
            packages_min: '',
            packages_max: '',
            confirmation_status: '',
            confirmation_source: '',
        },
        columns: [
            { key: 'run_number', label: 'Run #' },
            { key: 'recipient', label: 'Recipient' },
            { key: 'destination', label: 'Destination' },
            { key: 'courier', label: 'Courier' },
            { key: 'handoff_at', label: 'Handed Off' },
            { key: 'packages', label: 'Packages' },
            { key: 'status', label: 'Status' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            run_number: true,
            recipient: true,
            destination: true,
            courier: true,
            handoff_at: true,
            packages: true,
            status: true,
            actions: true,
        },

        init() {
            this.fetchData();
            this.$nextTick(() => this.initDateRange());
        },

        hoursLabel(stop) {
            const hours = stop.hours_ago ?? stop.hours_since_handoff;
            if (hours === null || hours === undefined) return '-';
            return `${hours}h ago`;
        },

        openActionModal(stop, action) {
            this.actionStop = stop;
            this.actionType = action;
            this.actionNotes = '';
            this.actionModalOpen = true;
        },

        closeActionModal(force = false) {
            if (this.submitting && !force) return;
            this.actionModalOpen = false;
            this.actionStop = null;
            this.actionNotes = '';
            this.actionType = 'delivered';
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    search: this.search,
                });
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        params.set(key, value);
                    }
                });
                const response = await fetch(config.dataUrl + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const json = await response.json();
                this.stops = json.data || [];
                this.meta = { ...this.meta, ...(json.meta || {}) };
                this.currentPage = this.meta.current_page || this.currentPage;
            } catch (e) {
                console.error('Failed to fetch pending confirmations', e);
                window.showToast?.('Unable to load pending confirmations.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async submitAction(stop, action) {
            if (!stop || !action) return;
            this.submitting = true;
            try {
                const url = config.confirmUrlTemplate
                    .replace('__RUN__', stop.run_id)
                    .replace('__STOP__', stop.id);

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                    },
                    body: JSON.stringify({ action, notes: this.actionNotes }),
                });

                const result = await response.json();
                if (!response.ok || result.success === false) {
                    throw new Error(result.message || 'Request failed');
                }

                this.stops = this.stops.filter((item) => item.id !== stop.id);
                this.meta.total = Math.max(0, Number(this.meta.total || 0) - 1);
                this.closeActionModal(true);

                const label = action === 'delivered' ? 'confirmed as delivered' : 'marked as failed';
                window.showToast?.('Delivery ' + label + ' successfully.', 'success');

                if (this.stops.length === 0 && this.currentPage > 1) {
                    this.currentPage -= 1;
                }
                this.fetchData();
            } catch (e) {
                console.error('Action failed', e);
                window.showToast?.(e.message || 'Something went wrong.', 'error');
            } finally {
                this.submitting = false;
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.meta.last_page || this.loading) return;
            this.currentPage = page;
            this.closeActionModal();
            this.fetchData();
        },

        applyFilters() {
            this.currentPage = 1;
            this.fetchData();
        },

        clearFilters() {
            Object.keys(this.filters).forEach((key) => {
                this.filters[key] = '';
            });
            if (this.$refs.handoffDateRange) {
                this.$refs.handoffDateRange.value = '';
            }
            this.currentPage = 1;
            this.fetchData();
        },

        initDateRange() {
            const setupPicker = () => {
                if (!this.$refs.handoffDateRange) return;
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                const $input = window.$(this.$refs.handoffDateRange);
                if ($input.data('daterangepicker')) return;

                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'left',
                    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                    ranges: {
                        Today: [window.moment(), window.moment()],
                        Yesterday: [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                        'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                        'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                        'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                    },
                });

                $input.on('apply.daterangepicker', (ev, picker) => {
                    this.filters.handoff_date_from = picker.startDate.format('YYYY-MM-DD');
                    this.filters.handoff_date_to = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.filters.handoff_date_from} - ${this.filters.handoff_date_to}`);
                });

                $input.on('cancel.daterangepicker', () => {
                    this.filters.handoff_date_from = '';
                    this.filters.handoff_date_to = '';
                    $input.val('');
                });
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            const cssId = 'daterangepicker-css';
            if (!document.getElementById(cssId)) {
                const link = document.createElement('link');
                link.id = cssId;
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                document.head.appendChild(link);
            }

            const loadScript = (id, src) => new Promise((resolve) => {
                if (document.getElementById(id)) return resolve();
                const script = document.createElement('script');
                script.id = id;
                script.src = src;
                script.onload = () => resolve();
                document.body.appendChild(script);
            });

            loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                .then(() => loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js'))
                .then(setupPicker);
        },

        toggleColumn(key) {
            if (key === 'actions') return;
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length || 1;
        },

        exportRows() {
            return this.stops.map((stop) => ({
                'Run #': stop.run_number || '',
                Recipient: stop.recipient_name || '',
                Phone: stop.recipient_phone || '',
                Destination: [stop.destination_town || stop.town, stop.destination_district || stop.district, stop.region].filter(Boolean).join(', '),
                Courier: stop.courier_name || '',
                'Courier Phone': stop.courier_phone || '',
                Vehicle: stop.vehicle_number || '',
                'Handed Off': stop.handed_off_at || stop.handoff_at || '',
                Packages: stop.packages_count || stop.total_packages || 0,
                Status: stop.needs_followup ? 'Needs Follow-up' : 'Pending',
            }));
        },

        exportData(type) {
            const rows = this.exportRows();
            if (!rows.length) {
                window.showToast?.('No pending confirmations to export.', 'warning');
                return;
            }

            if (type === 'print') {
                const htmlRows = rows.map((row) => `<tr>${Object.values(row).map((value) => `<td>${String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', '\'': '&#039;' }[char]))}</td>`).join('')}</tr>`).join('');
                const win = window.open('', '_blank');
                win.document.write(`<html><head><title>Pending Confirmations</title><style>body{font-family:Arial,sans-serif;padding:24px}table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #e2e8f0;padding:8px;text-align:left}th{background:#f8fafc}</style></head><body><h1>Pending Confirmations</h1><table><thead><tr>${Object.keys(rows[0]).map((key) => `<th>${key}</th>`).join('')}</tr></thead><tbody>${htmlRows}</tbody></table></body></html>`);
                win.document.close();
                win.print();
                return;
            }

            const headers = Object.keys(rows[0]);
            const csv = [headers.join(','), ...rows.map((row) => headers.map((header) => `"${String(row[header] ?? '').replace(/"/g, '""')}"`).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `pending-confirmations-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        },
    };
}
</script>
@endsection
