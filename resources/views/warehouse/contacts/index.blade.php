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
    ];
@endphp

@section('content')
<div x-data="contactQueuePage()" x-init="init()"
     data-contact-config="{{ e(json_encode($contactConfig, JSON_INVALID_UTF8_SUBSTITUTE)) }}">

    {{-- ── Stats Row ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
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

    {{-- ── Toolbar ─────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-6 p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center flex-1">
                {{-- Search --}}
                <div class="relative flex-1 max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" @@input="onSearch()"
                           placeholder="Search shipment, recipient, phone..."
                           class="w-full pl-10 pr-4 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-600/20 focus:border-orange-400 outline-none">
                </div>

                {{-- Status filter --}}
                <div class="relative w-full sm:w-48" x-data="{ open: false }">
                    <button type="button" @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <span x-text="statusFilterLabel">All Statuses</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @@click.away="open = false" x-transition
                         class="absolute left-0 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-xl p-1.5 z-50" style="display:none">
                        <template x-for="opt in statusOptions" :key="opt.value">
                            <button type="button" @@click="setStatusFilter(opt.value, opt.label); open = false"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                                    :class="statusFilter === opt.value ? 'bg-slate-100 font-semibold' : ''">
                                <span x-text="opt.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Worker filter --}}
                <div class="relative w-full sm:w-44" x-data="{ open: false }">
                    <button type="button" @@click="open = !open"
                            class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200 rounded-xl bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <span x-text="workerFilterLabel">All Workers</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @@click.away="open = false" x-transition
                         class="absolute left-0 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-xl p-1.5 z-50 max-h-60 overflow-y-auto" style="display:none">
                        <button type="button" @@click="setWorkerFilter('', 'All Workers'); open = false"
                                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50"
                                :class="workerFilter === '' ? 'bg-slate-100 font-semibold' : ''">All Workers</button>
                        <template x-for="w in cfg.workers" :key="w.id">
                            <button type="button" @@click="setWorkerFilter(w.id, w.name); open = false"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50"
                                    :class="workerFilter == w.id ? 'bg-slate-100 font-semibold' : ''">
                                <span x-text="w.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Auto-Assign --}}
                <button type="button" @@click="autoAssign()" :disabled="autoAssigning"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-sm font-semibold text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" :class="autoAssigning ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="autoAssigning ? 'Assigning...' : 'Auto-Assign'">Auto-Assign</span>
                </button>

                {{-- Worker Stats --}}
                <button type="button" @@click="openWorkerStats()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-sm font-semibold text-slate-700 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Worker Stats
                </button>
            </div>
        </div>
    </div>

    {{-- ── Loading ─────────────────────────────────────────────────────────── --}}
    <div x-show="loading" class="flex items-center justify-center py-16">
        <svg class="w-6 h-6 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
    </div>

    {{-- ── Empty State ─────────────────────────────────────────────────────── --}}
    <div x-show="!loading && tasks.length === 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm py-16 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-500">No contact tasks match your filters</p>
        <p class="text-xs text-slate-400 mt-1">Try adjusting your search or filter criteria</p>
    </div>

    {{-- ── Main Table ──────────────────────────────────────────────────────── --}}
    <div x-show="!loading && tasks.length > 0" class="bg-white rounded-2xl border border-slate-200 shadow-lg shadow-slate-200/50">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gradient-to-r from-slate-50 to-white border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Shipment #</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tracking</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Recipient</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Town</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assigned To</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Attempts</th>
                        <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="task in tasks" :key="task.id">
                        <tr class="hover:bg-slate-50/70 transition-colors"
                            :class="task.is_callback_due ? 'bg-rose-50/40 border-l-2 border-l-rose-400' : ''">
                            {{-- Shipment # --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-xs font-bold text-orange-700" x-text="task.shipment_number"></span>
                            </td>
                            {{-- Tracking --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-xs font-mono text-slate-600" x-text="task.tracking_number || '-'"></span>
                            </td>
                            {{-- Recipient --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-xs font-medium text-slate-900" x-text="task.recipient_name"></div>
                                <a :href="'tel:' + task.recipient_phone"
                                   class="text-[11px] text-orange-600 hover:text-orange-700 font-medium hover:underline"
                                   x-text="task.recipient_phone"></a>
                            </td>
                            {{-- Town --}}
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600" x-text="task.town || '-'"></td>
                            {{-- Assigned To --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span x-show="task.assigned_to_name" class="text-xs font-medium text-slate-700" x-text="task.assigned_to_name"></span>
                                <span x-show="!task.assigned_to_name" class="text-xs text-slate-400 italic">Unassigned</span>
                            </td>
                            {{-- Status --}}
                            <td class="px-4 py-3 whitespace-nowrap text-center">
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
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <button @@click="openCallHistory(task)"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full text-[11px] font-bold transition-colors"
                                        :class="task.attempts_count > 0 ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200 cursor-pointer' : 'bg-slate-100 text-slate-500'"
                                        :disabled="task.attempts_count === 0"
                                        x-text="task.attempts_count"></button>
                            </td>
                            {{-- Actions --}}
                            <td class="px-4 py-3 whitespace-nowrap text-right">
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

        {{-- Pagination --}}
        <div x-show="meta.last_page > 1" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
            <p class="text-[11px] text-slate-500">
                Showing <span class="font-bold" x-text="meta.from"></span> to <span class="font-bold" x-text="meta.to"></span> of <span class="font-bold" x-text="meta.total"></span>
            </p>
            <div class="flex items-center gap-1">
                <button @@click="fetchData(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                        class="px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">Prev</button>
                <button @@click="fetchData(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                        class="px-2.5 py-1.5 text-[11px] font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
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
                            <template x-for="ws in workerStatsData" :key="ws.worker_id">
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs font-semibold text-slate-800" x-text="ws.worker_name"></td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-[11px] font-bold" x-text="ws.assigned"></span>
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
                                    <td class="px-3 py-2.5 text-center text-xs text-slate-600" x-text="ws.avg_first_call_min ? ws.avg_first_call_min + ' min' : '-'"></td>
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
            this.cfg = JSON.parse(el.dataset.contactConfig || '{}');
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
                    this.tasks = json.data || [];
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

        // ── Assign task ───────────────────────────────────────────────────
        async assignTask(task, workerId) {
            try {
                const url = this.cfg.assignUrl.replace('__TASK__', task.id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: this.headers(true),
                    body: JSON.stringify({ worker_id: workerId }),
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
                    window.showToast?.(json.message || 'Auto-assign failed', 'error');
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
                    body: JSON.stringify(this.logCallForm),
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
