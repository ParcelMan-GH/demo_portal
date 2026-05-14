@extends('warehouse.layouts.app')

@section('title', 'Contact Queue')
@section('breadcrumb-parent', 'Operations')
@section('page-title', 'Contact Queue')

@php
    $contactConfig = [
        'dataUrl'        => route('warehouse.contacts.data'),
        'assignUrl'      => route('warehouse.contacts.assign', ['task' => '__TASK__']),
        'bulkAssignUrl'  => route('warehouse.contacts.bulk-assign'),
        'autoAssignUrl'  => route('warehouse.contacts.auto-assign'),
        'logCallUrl'     => route('warehouse.contacts.log-call', ['task' => '__TASK__']),
        'sendCodeUrl'    => route('warehouse.contacts.send-code', ['task' => '__TASK__']),
        'resolveUrl'     => route('warehouse.contacts.resolve', ['task' => '__TASK__']),
        'attemptsUrl'    => route('warehouse.contacts.attempts', ['task' => '__TASK__']),
        'workerStatsUrl' => route('warehouse.contacts.worker-stats'),
        'workers'        => $workers->toArray(),
        'warehouseName'  => $warehouse->name,
    ];
@endphp

@section('content')
<div x-data="contactQueuePage()" x-init="init()"
     data-contact-config="{{ e(json_encode($contactConfig, JSON_INVALID_UTF8_SUBSTITUTE)) }}">
    <script type="application/json" id="contact-queue-config">@json($contactConfig)</script>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5 mb-6">
        {{-- Unassigned --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Unassigned</p>
                    <p class="text-xl font-extrabold text-amber-600" x-text="localStats.unassigned">{{ $stats['unassigned'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        {{-- Assigned --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assigned</p>
                    <p class="text-xl font-extrabold text-blue-600" x-text="localStats.assigned">{{ $stats['assigned'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        {{-- In Progress --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">In Progress</p>
                    <p class="text-xl font-extrabold text-indigo-600" x-text="localStats.in_progress">{{ $stats['in_progress'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        {{-- Callbacks Due --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Callbacks Due</p>
                    <p class="text-xl font-extrabold text-rose-600" x-text="localStats.callbacks_due">{{ $stats['callbacks_due'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        {{-- Resolved Today --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Resolved Today</p>
                    <p class="text-xl font-extrabold text-emerald-600" x-text="localStats.resolved_today">{{ $stats['resolved_today'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 5a2 2 0 0 1 2-2h2.2a1 1 0 0 1 .95.68l1 3a1 1 0 0 1-.5 1.18l-1.5.75a12 12 0 0 0 5.24 5.24l.75-1.5a1 1 0 0 1 1.18-.5l3 1a1 1 0 0 1 .68.95V19a2 2 0 0 1-2 2h-1C8.37 21 3 15.63 3 9V5Z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Contact Queue</h2>
                            <p class="truncate text-sm text-slate-500">Recipient contact tasks for {{ $warehouse->name ?? 'this warehouse' }}.</p>
                        </div>
                    </div>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700" x-text="meta.total + ' tasks'">0 tasks</span>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" @@input="onSearch()" placeholder="Search shipment, tracking, recipient, phone..."
                               class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @@click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                            View
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <template x-for="column in columns" :key="column.key">
                                <button type="button" @@click="toggleColumn(column.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="column.label"></span>
                                    <svg x-show="visibleColumns[column.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <button type="button" @@click="exportData('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                            <button type="button" @@click="exportData('print'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
                    </div>

                    <button type="button" @@click="openWorkerStats()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Worker Stats
                    </button>

                    <button type="button" @@click="autoAssign()" :disabled="autoAssigning"
                            class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4" :class="autoAssigning ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span x-text="autoAssigning ? 'Assigning...' : 'Auto-Assign'">Auto-Assign</span>
                    </button>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="statusFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <template x-for="opt in statusOptions" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Worker</label>
                        <select x-model="workerFilter" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All Workers</option>
                            <template x-for="w in cfg.workers" :key="w.id">
                                <option :value="w.id" x-text="w.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>

            <div class="mb-3 flex flex-wrap gap-2" x-show="activeFilterChips().length">
                <template x-for="chip in activeFilterChips()" :key="chip.key">
                    <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                        <span x-text="chip.label"></span>
                        <button type="button" @@click="clearFilter(chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="loading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>
        <div class="divide-y divide-slate-100 md:hidden">
            <template x-if="!loading && tasks.length === 0">
                <div class="px-4 py-12 text-center">
                    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 5a2 2 0 0 1 2-2h2.2a1 1 0 0 1 .95.68l1 3a1 1 0 0 1-.5 1.18l-1.5.75a12 12 0 0 0 5.24 5.24l.75-1.5a1 1 0 0 1 1.18-.5l3 1a1 1 0 0 1 .68.95V19a2 2 0 0 1-2 2h-1C8.37 21 3 15.63 3 9V5Z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-500">No contact tasks match the current filters.</p>
                    <button type="button" @@click="clearFilters()" class="mt-2 text-xs font-bold text-orange-600 hover:underline">Clear filters</button>
                </div>
            </template>
            <template x-for="task in tasks" :key="task.id">
                <article class="px-4 py-4" :class="task.is_callback_due ? 'bg-rose-50/40' : ''">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-black text-orange-700" x-text="task.tracking_code || '-'"></p>
                            <h3 class="mt-1 text-base font-black text-slate-950" x-text="task.recipient_name || 'Recipient'"></h3>
                            <a :href="'tel:' + task.recipient_phone" class="mt-1 inline-flex text-sm font-bold text-slate-500" x-text="task.recipient_phone || '-'"></a>
                        </div>
                        <span class="inline-flex shrink-0 rounded-full px-3 py-1 text-[11px] font-black" :class="statusBadgeClass(task.status)" x-text="statusLabel(task.status)"></span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs font-bold text-slate-500">
                        <p><span class="text-slate-400">Shipment:</span> <span x-text="task.shipment_number || '-'"></span></p>
                        <p><span class="text-slate-400">Town:</span> <span x-text="task.delivery_town || '-'"></span></p>
                        <p><span class="text-slate-400">Worker:</span> <span x-text="task.assigned_to || 'Unassigned'"></span></p>
                        <p><span class="text-slate-400">Attempts:</span> <span x-text="task.attempts_count || 0"></span></p>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-end gap-2">
                        <template x-if="task.status === 'pending' && !task.assigned_to_id">
                            <select @@change="$event.target.value && assignTask(task, $event.target.value); $event.target.value = ''"
                                    class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700">
                                <option value="">Assign</option>
                                <template x-for="w in cfg.workers" :key="w.id">
                                    <option :value="w.id" x-text="w.name"></option>
                                </template>
                            </select>
                        </template>
                        <button x-show="task.attempts_count > 0" @@click="openCallHistory(task)" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700">History</button>
                        <button x-show="task.status === 'assigned' || task.status === 'in_progress' || task.is_callback_due" @@click="openLogCall(task)" class="h-10 rounded-xl bg-indigo-50 px-3 text-xs font-black text-indigo-700">Log Call</button>
                        <button x-show="task.status === 'assigned' || task.status === 'in_progress'" @@click="openResolve(task)" class="h-10 rounded-xl bg-orange-600 px-3 text-xs font-black text-white">Resolve</button>
                    </div>
                </article>
            </template>
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="w-full table-auto divide-y divide-slate-200/50 text-xs">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th x-show="visibleColumns.shipment_number" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Shipment</th>
                        <th x-show="visibleColumns.tracking_code" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Tracking</th>
                        <th x-show="visibleColumns.recipient" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Recipient</th>
                        <th x-show="visibleColumns.delivery_town" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Town</th>
                        <th x-show="visibleColumns.assigned_to" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Assigned To</th>
                        <th x-show="visibleColumns.status" class="px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th x-show="visibleColumns.attempts_count" class="px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Attempts</th>
                        <th x-show="visibleColumns.callback_at" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Callback</th>
                        <th x-show="visibleColumns.actions" class="px-4 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/50 bg-transparent">
                    <template x-if="!loading && tasks.length === 0">
                        <tr>
                            <td :colspan="visibleColumnCount()" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 5a2 2 0 0 1 2-2h2.2a1 1 0 0 1 .95.68l1 3a1 1 0 0 1-.5 1.18l-1.5.75a12 12 0 0 0 5.24 5.24l.75-1.5a1 1 0 0 1 1.18-.5l3 1a1 1 0 0 1 .68.95V19a2 2 0 0 1-2 2h-1C8.37 21 3 15.63 3 9V5Z"/></svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No contact tasks match the current filters</p>
                                    <button type="button" @@click="clearFilters()" class="text-xs font-semibold text-orange-600 hover:underline">Clear filters</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="task in tasks" :key="task.id">
                        <tr class="transition hover:bg-slate-50/70"
                            :class="task.is_callback_due ? 'bg-rose-50/40 border-l-2 border-l-rose-400' : ''">
                            {{-- Shipment # --}}
                            <td x-show="visibleColumns.shipment_number" class="whitespace-nowrap px-4 py-3">
                                <span class="text-xs font-bold text-orange-700" x-text="task.shipment_number || '-'"></span>
                            </td>
                            {{-- Tracking --}}
                            <td x-show="visibleColumns.tracking_code" class="whitespace-nowrap px-4 py-3">
                                <span class="font-mono text-xs font-bold text-slate-900" x-text="task.tracking_code || '-'"></span>
                            </td>
                            {{-- Recipient --}}
                            <td x-show="visibleColumns.recipient" class="whitespace-nowrap px-4 py-3">
                                <div class="text-xs font-bold text-slate-900" x-text="task.recipient_name || 'Recipient'"></div>
                                <a :href="'tel:' + task.recipient_phone"
                                   class="text-[11px] font-semibold text-slate-500 hover:text-orange-700 hover:underline"
                                   x-text="task.recipient_phone || '-'"></a>
                            </td>
                            {{-- Town --}}
                            <td x-show="visibleColumns.delivery_town" class="whitespace-nowrap px-4 py-3 text-xs font-semibold text-slate-600" x-text="task.delivery_town || '-'"></td>
                            {{-- Assigned To --}}
                            <td x-show="visibleColumns.assigned_to" class="whitespace-nowrap px-4 py-3">
                                <span x-show="task.assigned_to" class="text-xs font-semibold text-slate-700" x-text="task.assigned_to"></span>
                                <span x-show="!task.assigned_to" class="text-xs text-slate-400 italic">Unassigned</span>
                            </td>
                            {{-- Status --}}
                            <td x-show="visibleColumns.status" class="whitespace-nowrap px-3 py-3 text-center">
                                {{-- Task status badge --}}
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="{
                                          'bg-slate-100 text-slate-600': task.status === 'pending',
                                          'bg-blue-100 text-blue-700': task.status === 'assigned',
                                          'bg-indigo-100 text-indigo-700': task.status === 'in_progress',
                                          'bg-emerald-100 text-emerald-700': task.status === 'resolved',
                                      }" x-text="statusLabel(task.status)"></span>
                                {{-- Outcome badge (when resolved) --}}
                                <span x-show="task.outcome"
                                      class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-700': task.outcome === 'deliver',
                                          'bg-amber-100 text-amber-700': task.outcome === 'self_pickup',
                                          'bg-rose-100 text-rose-700': task.outcome === 'unreachable',
                                          'bg-red-100 text-red-700': task.outcome === 'wrong_number',
                                          'bg-violet-100 text-violet-700': task.outcome === 'callback',
                                      }" x-text="outcomeLabel(task.outcome)"></span>
                                {{-- Callback due badge --}}
                                <span x-show="task.is_callback_due"
                                      class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 animate-pulse">
                                    Call Back
                                </span>
                            </td>
                            {{-- Attempts --}}
                            <td x-show="visibleColumns.attempts_count" class="whitespace-nowrap px-3 py-3 text-center">
                                <button @@click="openCallHistory(task)"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full text-[11px] font-bold transition-colors"
                                        :class="task.attempts_count > 0 ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200 cursor-pointer' : 'bg-slate-100 text-slate-500'"
                                        :disabled="task.attempts_count === 0"
                                        x-text="task.attempts_count"></button>
                            </td>
                            <td x-show="visibleColumns.callback_at" class="whitespace-nowrap px-4 py-3 text-xs font-semibold text-slate-600" x-text="formatDate(task.callback_at)"></td>
                            {{-- Actions --}}
                            <td x-show="visibleColumns.actions" class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Assign dropdown (unassigned tasks) --}}
                                    <template x-if="task.status === 'pending' && !task.assigned_to_id">
                                        <div class="relative" x-data="{ assignOpen: false }">
                                            <button @@click="assignOpen = !assignOpen"
                                                    class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-[11px] font-bold rounded-lg border border-amber-200 transition-colors">
                                                Assign
                                            </button>
                                            <div x-show="assignOpen" x-cloak @@click.away="assignOpen = false" x-transition
                                                 class="absolute right-0 mt-1 w-44 rounded-xl border border-slate-200 bg-white shadow-xl p-1.5 z-50" style="display:none">
                                                <template x-for="w in cfg.workers" :key="w.id">
                                                    <button @@click="assignTask(task, w.id); assignOpen = false"
                                                            class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                                            x-text="w.name"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Log Call (assigned / in_progress / callback due) --}}
                                    <template x-if="task.status === 'assigned' || task.status === 'in_progress' || task.is_callback_due">
                                        <button @@click="openLogCall(task)"
                                                class="px-2.5 py-1.5 text-[11px] font-bold rounded-lg transition-colors"
                                                :class="task.is_callback_due ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200'">
                                            <span x-text="task.is_callback_due ? 'Call Back' : 'Log Call'"></span>
                                        </button>
                                    </template>

                                    {{-- Resolve (assigned / in_progress) --}}
                                    <template x-if="task.status === 'assigned' || task.status === 'in_progress'">
                                        <button @@click="openResolve(task)"
                                                class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[11px] font-bold rounded-lg border border-emerald-200 transition-colors">
                                            Resolve
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs font-semibold text-slate-600">
                    Showing <span x-text="meta.from || 0"></span> to <span x-text="meta.to || 0"></span> of <span x-text="meta.total || 0"></span>
                </div>

                <div class="flex items-center justify-between gap-3 sm:justify-end">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-600">Rows</span>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @@click="open = !open" class="inline-flex min-w-16 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700">
                                <span x-text="meta.per_page || 20"></span>
                                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full right-0 z-50 mb-1 w-20 rounded-xl border border-slate-200 bg-white p-1 shadow-lg" style="display:none">
                                <button type="button" @@click="setPerPage(10); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="meta.per_page == 10 ? 'bg-slate-100' : ''">10</button>
                                <button type="button" @@click="setPerPage(20); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="meta.per_page == 20 ? 'bg-slate-100' : ''">20</button>
                                <button type="button" @@click="setPerPage(50); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="meta.per_page == 50 ? 'bg-slate-100' : ''">50</button>
                                <button type="button" @@click="setPerPage(100); open = false" class="w-full rounded-lg px-2 py-1.5 text-center text-xs font-bold text-slate-700 hover:bg-slate-100" :class="meta.per_page == 100 ? 'bg-slate-100' : ''">100</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <button @@click="previousPage()" :disabled="meta.current_page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="meta.current_page || 1"></span> / <span x-text="meta.last_page || 1"></span></div>
                        <button @@click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         MODALS
         ══════════════════════════════════════════════════════════════════════ --}}

    {{-- ── Log Call Modal ──────────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="logCallOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @@click="logCallOpen = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-5" @@click.outside="logCallOpen = false">
                {{-- Header --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Log Call</h3>
                    <button @@click="logCallOpen = false" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Task info --}}
                <div class="bg-orange-50 rounded-xl p-3 border border-orange-200">
                    <p class="text-xs text-orange-900 font-bold" x-text="logCallTask?.shipment_number"></p>
                    <p class="text-[11px] text-orange-800 mt-0.5">
                        <span x-text="logCallTask?.recipient_name"></span> &middot;
                        <a :href="'tel:' + logCallTask?.recipient_phone" class="underline" x-text="logCallTask?.recipient_phone"></a>
                    </p>
                </div>

                {{-- Call outcome --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-2">Call Outcome</label>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="opt in callOutcomeOptions" :key="opt.value">
                            <button type="button" @@click="logCallForm.outcome = opt.value"
                                    class="px-3 py-2.5 text-xs font-semibold rounded-xl border-2 transition-all"
                                    :class="logCallForm.outcome === opt.value
                                        ? 'border-orange-500 bg-orange-50 text-orange-700'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                                <span x-text="opt.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea x-model="logCallForm.notes" rows="2"
                              class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none resize-none"
                              placeholder="Any details about the call..."></textarea>
                </div>

                {{-- Error --}}
                <div x-show="logCallError" class="px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-xs text-red-700 font-medium" x-text="logCallError"></p>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2.5 pt-1">
                    <button @@click="submitLogCall()" :disabled="logCallSubmitting || !logCallForm.outcome"
                            class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-[0.98]">
                        <span x-show="!logCallSubmitting">Log Call</span>
                        <span x-show="logCallSubmitting">Logging...</span>
                    </button>
                    <button @@click="logCallOpen = false"
                            class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    </template>

    {{-- ── Resolve Modal ───────────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="resolveOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @@click="resolveOpen = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-5" @@click.outside="resolveOpen = false">
                {{-- Header --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Resolve Contact Task</h3>
                    <button @@click="resolveOpen = false" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Task info --}}
                <div class="bg-orange-50 rounded-xl p-3 border border-orange-200">
                    <p class="text-xs text-orange-900 font-bold" x-text="resolveTask?.shipment_number"></p>
                    <p class="text-[11px] text-orange-800 mt-0.5">
                        <span x-text="resolveTask?.recipient_name"></span> &middot;
                        <span x-text="resolveTask?.recipient_phone"></span>
                    </p>
                </div>

                {{-- Outcome selection --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-2">Recipient Decision</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @@click="resolveForm.outcome = 'deliver'"
                                class="px-3 py-3 text-xs font-bold rounded-xl border-2 transition-all flex flex-col items-center gap-1"
                                :class="resolveForm.outcome === 'deliver'
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            Wants Delivery
                        </button>
                        <button type="button" @@click="resolveForm.outcome = 'self_pickup'"
                                class="px-3 py-3 text-xs font-bold rounded-xl border-2 transition-all flex flex-col items-center gap-1"
                                :class="resolveForm.outcome === 'self_pickup'
                                    ? 'border-amber-500 bg-amber-50 text-amber-700'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Will Pick Up
                        </button>
                        <button type="button" @@click="resolveForm.outcome = 'unreachable'"
                                class="px-3 py-3 text-xs font-bold rounded-xl border-2 transition-all flex flex-col items-center gap-1"
                                :class="resolveForm.outcome === 'unreachable'
                                    ? 'border-rose-500 bg-rose-50 text-rose-700'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Unreachable
                        </button>
                        <button type="button" @@click="resolveForm.outcome = 'wrong_number'"
                                class="px-3 py-3 text-xs font-bold rounded-xl border-2 transition-all flex flex-col items-center gap-1"
                                :class="resolveForm.outcome === 'wrong_number'
                                    ? 'border-red-500 bg-red-50 text-red-700'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Wrong Number
                        </button>
                        <button type="button" @@click="resolveForm.outcome = 'callback'"
                                class="col-span-2 px-3 py-3 text-xs font-bold rounded-xl border-2 transition-all flex items-center justify-center gap-2"
                                :class="resolveForm.outcome === 'callback'
                                    ? 'border-violet-500 bg-violet-50 text-violet-700'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Schedule Callback
                        </button>
                    </div>
                </div>

                {{-- Callback datetime (only when callback selected) --}}
                <div x-show="resolveForm.outcome === 'callback'" x-transition class="space-y-2">
                    <label class="block text-[11px] font-bold text-slate-600">Callback Date & Time <span class="text-red-400">*</span></label>
                    <input type="datetime-local" x-model="resolveForm.callback_at"
                           class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-violet-600/20 focus:border-violet-400 outline-none">
                </div>

                {{-- Recipient confirmation code (deliver / self_pickup only) --}}
                <div x-show="requiresVerification(resolveForm.outcome)" x-transition
                     class="rounded-2xl border-2 border-emerald-200 bg-emerald-50/50 p-4 space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-emerald-900">Confirm with recipient</p>
                            <p class="text-[11px] text-emerald-800/80 mt-0.5 leading-relaxed">
                                Send a code to the recipient, ask them to read it back on the call, then enter it below.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @@click="sendConfirmationCode()"
                                :disabled="codeSending || codeResendAfter > 0"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="codeSending" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-show="!codeSentAt && !codeSending">Send Code</span>
                            <span x-show="codeSentAt && codeResendAfter === 0 && !codeSending">Resend Code</span>
                            <span x-show="codeResendAfter > 0" x-text="'Resend in ' + codeResendAfter + 's'"></span>
                            <span x-show="codeSending">Sending…</span>
                        </button>
                        <span x-show="codeSentAt" class="text-[11px] text-emerald-700">
                            Sent to <span class="font-mono" x-text="resolveTask?.recipient_phone"></span>
                        </span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Code from recipient</label>
                        <input type="text" x-model="resolveCode" maxlength="10"
                               placeholder="e.g. XK7R4Q"
                               @@input="resolveCode = resolveCode.toUpperCase(); resolveCodeError = ''"
                               class="w-full px-3.5 py-2.5 text-base border-2 rounded-xl bg-white font-mono tracking-[0.3em] uppercase text-slate-900 placeholder-slate-400 outline-none transition-colors focus:ring-2 focus:ring-emerald-400/20 focus:border-emerald-400"
                               :class="resolveCodeError ? 'border-rose-300' : 'border-slate-200'">
                        <p x-show="resolveCodeError" x-text="resolveCodeError" class="mt-1.5 text-[11px] text-rose-600 font-medium"></p>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea x-model="resolveForm.notes" rows="2"
                              class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none resize-none"
                              placeholder="Additional resolution notes..."></textarea>
                </div>

                {{-- Error --}}
                <div x-show="resolveError" class="px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-xs text-red-700 font-medium" x-text="resolveError"></p>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2.5 pt-1">
                    <button @@click="submitResolve()"
                            :disabled="resolveSubmitting || !resolveForm.outcome || (resolveForm.outcome === 'callback' && !resolveForm.callback_at)"
                            class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-all active:scale-[0.98]">
                        <span x-show="!resolveSubmitting">Resolve</span>
                        <span x-show="resolveSubmitting">Resolving...</span>
                    </button>
                    <button @@click="resolveOpen = false"
                            class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    </template>

    {{-- ── Call History Modal ──────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="historyOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @@click="historyOpen = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-5" @@click.outside="historyOpen = false">
                {{-- Header --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Call History</h3>
                    <button @@click="historyOpen = false" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Task info --}}
                <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                    <p class="text-xs text-slate-900 font-bold" x-text="historyTask?.shipment_number"></p>
                    <p class="text-[11px] text-slate-600 mt-0.5" x-text="historyTask?.recipient_name + ' - ' + historyTask?.recipient_phone"></p>
                </div>

                {{-- Loading --}}
                <div x-show="historyLoading" class="flex justify-center py-6">
                    <svg class="w-5 h-5 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </div>

                {{-- Empty --}}
                <div x-show="!historyLoading && historyItems.length === 0" class="text-center py-6">
                    <p class="text-sm text-slate-400">No call attempts recorded</p>
                </div>

                {{-- Attempts list --}}
                <div x-show="!historyLoading && historyItems.length > 0" class="space-y-3 max-h-80 overflow-y-auto">
                    <template x-for="(attempt, idx) in historyItems" :key="idx">
                        <div class="flex gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold"
                                 :class="{
                                     'bg-emerald-100 text-emerald-700': attempt.outcome === 'answered',
                                     'bg-slate-100 text-slate-600': attempt.outcome === 'no_answer',
                                     'bg-amber-100 text-amber-700': attempt.outcome === 'busy',
                                     'bg-red-100 text-red-700': attempt.outcome === 'wrong_number',
                                     'bg-violet-100 text-violet-700': attempt.outcome === 'voicemail',
                                 }" x-text="idx + 1"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-800" x-text="attempt.caller_name"></span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                                          :class="{
                                              'bg-emerald-100 text-emerald-700': attempt.outcome === 'answered',
                                              'bg-slate-200 text-slate-600': attempt.outcome === 'no_answer',
                                              'bg-amber-100 text-amber-700': attempt.outcome === 'busy',
                                              'bg-red-100 text-red-700': attempt.outcome === 'wrong_number',
                                              'bg-violet-100 text-violet-700': attempt.outcome === 'voicemail',
                                          }" x-text="attempt.outcome?.replace('_', ' ')"></span>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-0.5" x-text="attempt.called_at"></p>
                                <p x-show="attempt.notes" class="text-[11px] text-slate-600 mt-1 italic" x-text="attempt.notes"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Close --}}
                <div class="flex justify-end pt-1">
                    <button @@click="historyOpen = false"
                            class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </template>

    {{-- ── Worker Stats Modal ──────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="workerStatsOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @@click="workerStatsOpen = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl p-6 space-y-5" @@click.outside="workerStatsOpen = false">
                {{-- Header --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Worker Stats</h3>
                    <button @@click="workerStatsOpen = false" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Loading --}}
                <div x-show="workerStatsLoading" class="flex justify-center py-6">
                    <svg class="w-5 h-5 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </div>

                {{-- Empty --}}
                <div x-show="!workerStatsLoading && workerStatsData.length === 0" class="text-center py-6">
                    <p class="text-sm text-slate-400">No worker stats available</p>
                </div>

                {{-- Stats table --}}
                <div x-show="!workerStatsLoading && workerStatsData.length > 0" class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2.5 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Worker</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assigned</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pending</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Resolved</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Callbacks</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Avg 1st Call</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Deliver</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pickup</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Unreach.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="ws in workerStatsData" :key="ws.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-800" x-text="ws.name"></td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-[11px] font-bold" x-text="ws.total_assigned"></span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 text-amber-700 text-[11px] font-bold" x-text="ws.pending"></span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-bold" x-text="ws.resolved"></span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-rose-100 text-rose-700 text-[11px] font-bold" x-text="ws.callbacks_due"></span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center text-xs text-slate-600" x-text="ws.avg_first_call_minutes ? ws.avg_first_call_minutes + ' min' : '-'"></td>
                                    <td class="px-3 py-2.5 text-center text-xs text-emerald-600 font-medium" x-text="ws.outcomes?.deliver ?? 0"></td>
                                    <td class="px-3 py-2.5 text-center text-xs text-amber-600 font-medium" x-text="ws.outcomes?.self_pickup ?? 0"></td>
                                    <td class="px-3 py-2.5 text-center text-xs text-rose-600 font-medium" x-text="ws.outcomes?.unreachable ?? 0"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Close --}}
                <div class="flex justify-end pt-1">
                    <button @@click="workerStatsOpen = false"
                            class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 transition-colors">Close</button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function contactQueuePage() {
    return {
        // Config
        cfg: {},

        // Table state
        tasks: [],
        meta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        loading: false,
        search: '',
        _searchTimeout: null,
        statusFilter: '',
        statusFilterLabel: 'All Statuses',
        workerFilter: '',
        workerFilterLabel: 'All Workers',
        showFilters: false,
        columns: [
            { key: 'shipment_number', label: 'Shipment' },
            { key: 'tracking_code', label: 'Tracking' },
            { key: 'recipient', label: 'Recipient' },
            { key: 'delivery_town', label: 'Town' },
            { key: 'assigned_to', label: 'Assigned To' },
            { key: 'status', label: 'Status' },
            { key: 'attempts_count', label: 'Attempts' },
            { key: 'callback_at', label: 'Callback' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            shipment_number: false,
            tracking_code: true,
            recipient: true,
            delivery_town: true,
            assigned_to: true,
            status: true,
            attempts_count: true,
            callback_at: false,
            actions: true,
        },

        // Stats
        localStats: {
            unassigned: {{ $stats['unassigned'] ?? 0 }},
            assigned: {{ $stats['assigned'] ?? 0 }},
            in_progress: {{ $stats['in_progress'] ?? 0 }},
            callbacks_due: {{ $stats['callbacks_due'] ?? 0 }},
            resolved_today: {{ $stats['resolved_today'] ?? 0 }},
        },

        // Status options
        statusOptions: [
            { value: '', label: 'All Statuses' },
            { value: 'pending', label: 'Pending' },
            { value: 'assigned', label: 'Assigned' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'resolved', label: 'Resolved' },
            { value: 'callbacks_due', label: 'Callbacks Due' },
        ],

        // Call outcome options
        callOutcomeOptions: [
            { value: 'answered', label: 'Answered' },
            { value: 'no_answer', label: 'No Answer' },
            { value: 'busy', label: 'Busy' },
            { value: 'wrong_number', label: 'Wrong Number' },
            { value: 'voicemail', label: 'Voicemail' },
        ],

        // Auto-assign
        autoAssigning: false,

        // Log Call modal
        logCallOpen: false,
        logCallTask: null,
        logCallSubmitting: false,
        logCallError: '',
        logCallForm: { outcome: '', notes: '' },

        // Resolve modal
        resolveOpen: false,
        resolveTask: null,
        resolveSubmitting: false,
        resolveError: '',
        resolveForm: { outcome: '', notes: '', callback_at: '' },

        // Confirmation code state (deliver / self_pickup only)
        resolveCode: '',
        resolveCodeError: '',
        codeSending: false,
        codeSentAt: null,
        codeExpiresAt: null,
        codeResendAfter: 0,
        codeCountdownTimer: null,

        // Call History modal
        historyOpen: false,
        historyTask: null,
        historyLoading: false,
        historyItems: [],

        // Worker Stats modal
        workerStatsOpen: false,
        workerStatsLoading: false,
        workerStatsData: [],

        // ── Init ──────────────────────────────────────────────────────────
        init() {
            const el = this.$root || document.querySelector('[data-contact-config]');
            const scriptConfig = document.getElementById('contact-queue-config')?.textContent;
            this.cfg = scriptConfig ? JSON.parse(scriptConfig) : JSON.parse(el.dataset.contactConfig || '{}');
            this.fetchData(1);
        },

        // ── Helpers ───────────────────────────────────────────────────────
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        headers(json = false) {
            const h = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken(),
            };
            if (json) h['Content-Type'] = 'application/json';
            return h;
        },

        statusLabel(status) {
            const map = { pending: 'Pending', assigned: 'Assigned', in_progress: 'In Progress', resolved: 'Resolved' };
            return map[status] || status;
        },

        statusBadgeClass(status) {
            const map = {
                pending: 'bg-amber-50 text-amber-700 border border-amber-200',
                assigned: 'bg-blue-50 text-blue-700 border border-blue-200',
                in_progress: 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                resolved: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            };
            return map[status] || 'bg-slate-50 text-slate-700 border border-slate-200';
        },

        formatDate(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });
        },

        outcomeLabel(outcome) {
            const map = {
                deliver: 'Delivery', self_pickup: 'Self Pickup', unreachable: 'Unreachable',
                wrong_number: 'Wrong #', callback: 'Callback',
            };
            return map[outcome] || outcome;
        },

        // ── Data fetching ─────────────────────────────────────────────────
        fetchData(page) {
            this.loading = true;
            const params = new URLSearchParams({ page, per_page: this.meta.per_page });
            if (this.search) params.set('search', this.search);
            if (this.statusFilter) params.set('status', this.statusFilter);
            if (this.workerFilter) params.set('worker_id', this.workerFilter);

            fetch(this.cfg.dataUrl + '?' + params.toString(), { headers: this.headers() })
                .then(r => r.json())
                .then(json => {
                    this.tasks = (json.data || []).map((task) => ({
                        ...task,
                        tracking_code: task.tracking_code || task.tracking_number || '',
                        delivery_town: task.delivery_town || task.town || '',
                        assigned_to: task.assigned_to || task.assigned_to_name || '',
                        is_callback_due: Boolean(task.is_callback_due),
                    }));
                    this.meta = json.meta || this.meta;
                    if (json.stats) {
                        this.localStats = json.stats;
                    }
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },

        onSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.fetchData(1), 350);
        },

        setStatusFilter(value, label) {
            this.statusFilter = value;
            this.statusFilterLabel = label;
            this.fetchData(1);
        },

        setWorkerFilter(value, label) {
            this.workerFilter = value;
            this.workerFilterLabel = label;
            this.fetchData(1);
        },

        applyFilters() {
            this.statusFilterLabel = this.statusOptions.find((opt) => opt.value === this.statusFilter)?.label || 'All Statuses';
            const worker = (this.cfg.workers || []).find((item) => String(item.id) === String(this.workerFilter));
            this.workerFilterLabel = worker ? worker.name : 'All Workers';
            this.fetchData(1);
        },

        clearFilters() {
            this.statusFilter = '';
            this.workerFilter = '';
            this.statusFilterLabel = 'All Statuses';
            this.workerFilterLabel = 'All Workers';
            this.fetchData(1);
        },

        clearFilter(key) {
            if (key === 'status') {
                this.statusFilter = '';
                this.statusFilterLabel = 'All Statuses';
            }
            if (key === 'worker') {
                this.workerFilter = '';
                this.workerFilterLabel = 'All Workers';
            }
            this.fetchData(1);
        },

        activeFilterChips() {
            const chips = [];
            if (this.statusFilter) chips.push({ key: 'status', label: `Status: ${this.statusFilterLabel}` });
            if (this.workerFilter) chips.push({ key: 'worker', label: `Worker: ${this.workerFilterLabel}` });
            return chips;
        },

        toggleColumn(key) {
            if (key === 'actions') return;
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length || 1;
        },

        setPerPage(value) {
            this.meta.per_page = value;
            this.fetchData(1);
        },

        previousPage() {
            if ((this.meta.current_page || 1) > 1) this.fetchData(this.meta.current_page - 1);
        },

        nextPage() {
            if ((this.meta.current_page || 1) < (this.meta.last_page || 1)) this.fetchData(this.meta.current_page + 1);
        },

        exportData(type) {
            if (type === 'print') {
                window.print();
                return;
            }
            const headers = ['Tracking', 'Recipient', 'Phone', 'Town', 'Assigned To', 'Status', 'Attempts'];
            const rows = this.tasks.map((task) => [
                task.tracking_code || '',
                task.recipient_name || '',
                task.recipient_phone || '',
                task.delivery_town || '',
                task.assigned_to || 'Unassigned',
                this.statusLabel(task.status),
                task.attempts_count || 0,
            ]);
            const csv = [headers, ...rows].map((row) => row.map((value) => `"${String(value).replaceAll('"', '""')}"`).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `contact-queue-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
            URL.revokeObjectURL(url);
        },

        // ── Assign task ───────────────────────────────────────────────────
        async assignTask(task, workerId) {
            if (!workerId) return;
            try {
                const url = this.cfg.assignUrl.replace('__TASK__', task.id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: this.headers(true),
                    body: JSON.stringify({ user_id: workerId }),
                });
                const json = await res.json();
                if (json.success) {
                    window.showToast?.('Task assigned successfully', 'success');
                    this.fetchData(this.meta.current_page);
                } else {
                    window.showToast?.(json.message || 'Failed to assign task', 'error');
                }
            } catch {
                window.showToast?.('An unexpected error occurred', 'error');
            }
        },

        // ── Auto-assign ──────────────────────────────────────────────────
        async autoAssign() {
            if (!this.cfg.autoAssignUrl) {
                window.showToast?.('Auto-assign route is not configured.', 'error');
                return;
            }
            this.autoAssigning = true;
            try {
                const res = await fetch(this.cfg.autoAssignUrl, {
                    method: 'POST',
                    headers: this.headers(true),
                });
                const json = await res.json();
                if (json.success) {
                    window.showToast?.(json.message || 'Tasks auto-assigned', 'success');
                    this.fetchData(1);
                } else {
                    window.showToast?.(json.message || 'Auto-assign failed', json.reason === 'no_pending_tasks' ? 'info' : 'error');
                }
            } catch {
                window.showToast?.('An unexpected error occurred', 'error');
            }
            this.autoAssigning = false;
        },

        // ── Log Call modal ────────────────────────────────────────────────
        openLogCall(task) {
            this.logCallTask = task;
            this.logCallForm = { outcome: '', notes: '' };
            this.logCallError = '';
            this.logCallOpen = true;
        },

        async submitLogCall() {
            this.logCallSubmitting = true;
            this.logCallError = '';
            try {
                const url = this.cfg.logCallUrl.replace('__TASK__', this.logCallTask.id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: this.headers(true),
                    body: JSON.stringify({
                        call_outcome: this.logCallForm.outcome,
                        notes: this.logCallForm.notes,
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    this.logCallOpen = false;
                    window.showToast?.('Call logged successfully', 'success');
                    this.fetchData(this.meta.current_page);
                } else if (json.errors) {
                    this.logCallError = Object.values(json.errors).flat().join(', ');
                } else {
                    this.logCallError = json.message || 'Failed to log call.';
                }
            } catch {
                this.logCallError = 'An unexpected error occurred.';
            }
            this.logCallSubmitting = false;
        },

        // ── Resolve modal ─────────────────────────────────────────────────
        requiresVerification(outcome) {
            return outcome === 'deliver' || outcome === 'self_pickup';
        },

        openResolve(task) {
            this.resolveTask = task;
            this.resolveForm = { outcome: '', notes: '', callback_at: '' };
            this.resolveError = '';
            this.resetCodeState();
            this.resolveOpen = true;
        },

        resetCodeState() {
            this.resolveCode = '';
            this.resolveCodeError = '';
            this.codeSending = false;
            this.codeSentAt = null;
            this.codeExpiresAt = null;
            this.codeResendAfter = 0;
            if (this.codeCountdownTimer) {
                clearInterval(this.codeCountdownTimer);
                this.codeCountdownTimer = null;
            }
        },

        startResendCountdown(seconds) {
            this.codeResendAfter = seconds;
            if (this.codeCountdownTimer) clearInterval(this.codeCountdownTimer);
            this.codeCountdownTimer = setInterval(() => {
                this.codeResendAfter = Math.max(0, this.codeResendAfter - 1);
                if (this.codeResendAfter === 0) {
                    clearInterval(this.codeCountdownTimer);
                    this.codeCountdownTimer = null;
                }
            }, 1000);
        },

        async sendConfirmationCode() {
            if (!this.resolveTask || this.codeSending || this.codeResendAfter > 0) return;
            this.codeSending = true;
            this.resolveCodeError = '';
            try {
                const url = this.cfg.sendCodeUrl.replace('__TASK__', this.resolveTask.id);
                const res = await fetch(url, { method: 'POST', headers: this.headers(true) });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success) {
                    this.codeSentAt = json.data?.sent_at ?? new Date().toISOString();
                    this.codeExpiresAt = json.data?.expires_at ?? null;
                    this.startResendCountdown(json.data?.resend_after_seconds ?? 60);
                    window.showToast?.(json.message || 'Code sent to recipient', 'success');
                } else {
                    if (json.data?.resend_after_seconds) {
                        this.startResendCountdown(json.data.resend_after_seconds);
                    }
                    window.showToast?.(json.message || 'Failed to send code', 'error');
                }
            } catch {
                window.showToast?.('Network error', 'error');
            }
            this.codeSending = false;
        },

        async submitResolve() {
            if (this.requiresVerification(this.resolveForm.outcome) && !this.resolveCode.trim()) {
                this.resolveCodeError = 'Enter the code the recipient read to you.';
                return;
            }
            this.resolveSubmitting = true;
            this.resolveError = '';
            this.resolveCodeError = '';
            try {
                const url = this.cfg.resolveUrl.replace('__TASK__', this.resolveTask.id);
                const payload = { outcome: this.resolveForm.outcome, notes: this.resolveForm.notes };
                if (this.resolveForm.outcome === 'callback' && this.resolveForm.callback_at) {
                    payload.callback_at = this.resolveForm.callback_at;
                }
                if (this.requiresVerification(this.resolveForm.outcome)) {
                    payload.confirmation_code = this.resolveCode.trim().toUpperCase();
                }
                const res = await fetch(url, {
                    method: 'POST',
                    headers: this.headers(true),
                    body: JSON.stringify(payload),
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success) {
                    this.resolveOpen = false;
                    this.resetCodeState();
                    window.showToast?.('Task resolved successfully', 'success');
                    this.fetchData(this.meta.current_page);
                } else if (json.code) {
                    // Verification failure (invalid/expired/missing/exhausted) — inline error, keep modal open.
                    this.resolveCodeError = json.message || 'Code could not be verified.';
                } else if (json.errors) {
                    this.resolveError = Object.values(json.errors).flat().join(', ');
                } else {
                    this.resolveError = json.message || 'Failed to resolve task.';
                }
            } catch {
                this.resolveError = 'An unexpected error occurred.';
            }
            this.resolveSubmitting = false;
        },

        // ── Call History modal ────────────────────────────────────────────
        async openCallHistory(task) {
            if (task.attempts_count === 0) return;
            this.historyTask = task;
            this.historyItems = [];
            this.historyLoading = true;
            this.historyOpen = true;
            try {
                const url = this.cfg.attemptsUrl.replace('__TASK__', task.id);
                const res = await fetch(url, { headers: this.headers() });
                const json = await res.json();
                this.historyItems = json.data || [];
            } catch {
                this.historyItems = [];
            }
            this.historyLoading = false;
        },

        // ── Worker Stats modal ────────────────────────────────────────────
        async openWorkerStats() {
            this.workerStatsData = [];
            this.workerStatsLoading = true;
            this.workerStatsOpen = true;
            try {
                const res = await fetch(this.cfg.workerStatsUrl, { headers: this.headers() });
                const json = await res.json();
                this.workerStatsData = json.data || [];
            } catch {
                this.workerStatsData = [];
            }
            this.workerStatsLoading = false;
        },
    };
}
</script>
@endpush
