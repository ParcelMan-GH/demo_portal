@extends('warehouse.layouts.app')

@section('title', 'Recipient Desk')
@section('breadcrumb-parent', 'Operations')
@section('page-title', 'Recipient Desk')

@php
    $contactConfig = [
        'dataUrl'        => route('warehouse.contacts.data'),
        'assignUrl'      => route('warehouse.contacts.assign', ['task' => '__TASK__']),
        'bulkAssignUrl'  => route('warehouse.contacts.bulk-assign'),
        'logCallUrl'     => route('warehouse.contacts.log-call', ['task' => '__TASK__']),
        'sendCodeUrl'    => route('warehouse.contacts.send-code', ['task' => '__TASK__']),
        'resolveUrl'     => route('warehouse.contacts.resolve', ['task' => '__TASK__']),
        'handoverUrl'    => route('warehouse.contacts.handover', ['task' => '__TASK__']),
        'attemptsUrl'    => route('warehouse.contacts.attempts', ['task' => '__TASK__']),
        'workerStatsUrl' => route('warehouse.contacts.worker-stats'),
        'workers'        => $workers->toArray(),
        'warehouseName'  => $warehouse->name,
    ];
@endphp

@section('content')
<div x-data="contactQueuePage()" x-init="init()"
     data-contact-config="{{ json_encode($contactConfig, JSON_INVALID_UTF8_SUBSTITUTE) }}"
     class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

    <script type="application/json" id="contact-queue-config">@json($contactConfig)</script>

    {{-- ═══════════ HEADER & DATE FILTER ═══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Active Contact Queue</h1>
                
                {{-- Active Date Filter Dropdown --}}
                <div x-data="{ dateOpen: false }" class="relative">
                    <button type="button" @click="dateOpen = !dateOpen" class="px-3.5 py-1.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl shadow-sm flex items-center gap-2 hover:bg-slate-50 transition-colors">
                        <span x-text="dateRangeLabel">Today</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    
                    <div x-show="dateOpen" @click.away="dateOpen = false" x-transition class="absolute left-0 z-50 mt-1.5 w-36 bg-white border border-slate-200 rounded-xl shadow-lg p-1 text-xs font-medium" style="display:none">
                        <template x-for="opt in dateOptions" :key="opt.value">
                            <button type="button" @click="setDateRange(opt.value, opt.label); dateOpen = false" 
                                    class="w-full text-left px-3 py-2 rounded-lg transition-colors flex items-center justify-between"
                                    :class="dateRange === opt.value ? 'bg-orange-50 text-orange-600 font-bold' : 'text-slate-700 hover:bg-slate-50'"
                                    x-text="opt.label">
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <p class="text-slate-500 text-sm mt-1">Live monitoring of agent calls, claimed packages, and resolutions.</p>
        </div>

        <button type="button" @click="openWorkerStats()" class="px-5 py-3 bg-[#ea580c] hover:bg-[#c2410c] text-white text-sm font-semibold rounded-xl shadow-md shadow-orange-500/20 transition-all active:scale-95 flex items-center gap-2 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Agents Performance
        </button>
    </div>

    {{-- ═══════════ STATS CARDS ═══════════ --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500">Number Of Agents</span>
            <span class="text-4xl font-normal text-slate-900" x-text="cfg.workers?.length || 0">{{ count($workers) }}</span>
        </div>

        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500">Total Claimed Items</span>
            <span class="text-4xl font-normal text-slate-900" x-text="localStats.assigned">{{ $stats['assigned'] ?? 0 }}</span>
        </div>

        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500">Total Calls Made</span>
            <span class="text-4xl font-normal text-slate-900" x-text="localStats.in_progress">{{ $stats['in_progress'] ?? 0 }}</span>
        </div>

        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500">Total Payment Made</span>
            <span class="text-4xl font-normal text-slate-900" x-text="localStats.resolved_today">{{ $stats['resolved_today'] ?? 0 }}</span>
        </div>

        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500">Total Amount</span>
            <span class="text-4xl font-normal text-slate-900" x-text="'GH₵ ' + (localStats.total_amount || '670')">GH₵ {{ $stats['total_amount'] ?? 670 }}</span>
        </div>
    </div>

    {{-- ═══════════ TABLE CONTAINER ═══════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:w-80">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @input="onSearch()" placeholder="Search Tracking, Recipient, Agent"
                       class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-all">
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <button type="button" @click="showFilters = !showFilters"
                        class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5"
                        :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700' : ''">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                    Filter
                </button>

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="px-4 py-2.5 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 002 3h12a3 3 0 002-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-1 w-36 bg-white border border-slate-200 rounded-xl shadow-lg p-1" style="display:none">
                        <button type="button" @click="exportData('csv'); open = false" class="w-full text-left px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 rounded-lg">CSV</button>
                        <button type="button" @click="exportData('print'); open = false" class="w-full text-left px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 rounded-lg">Print</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expanded Filter Panel --}}
        <div x-show="showFilters" x-transition class="p-4 bg-slate-50/80 border-t border-b border-slate-100" style="display:none">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select x-model="statusFilter" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <template x-for="opt in statusOptions" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Agent / Worker</label>
                    <select x-model="workerFilter" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 outline-none focus:ring-1 focus:ring-orange-500">
                        <option value="">All Workers</option>
                        <template x-for="w in cfg.workers" :key="w.id">
                            <option :value="w.id" x-text="w.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-end gap-2">
                <button type="button" @click="clearFilters()" class="px-3 py-1.5 text-xs text-slate-500 hover:text-slate-800 font-semibold">Clear</button>
                <button type="button" @click="applyFilters()" class="px-4 py-1.5 bg-orange-600 text-white rounded-lg text-xs font-semibold">Apply</button>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto relative">
            <div x-show="loading" x-cloak class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>

            <table class="w-full text-left border-collapse">
                <thead class="bg-[#FFF8F3] border-y border-orange-100/60">
                    <tr>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Tracking</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Recipient</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Contact</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Location</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Claimed by</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Status</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700 text-center">More</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-800 bg-white">
                    <template x-if="!loading && tasks.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">No recipient calls match the current filters.</td>
                        </tr>
                    </template>

                    <template x-for="task in tasks" :key="task.id">
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 font-mono font-normal text-slate-900" x-text="task.tracking_code || task.shipment_number || '-'"></td>
                            <td class="px-6 py-4 text-slate-900" x-text="task.recipient_name || 'Recipient'"></td>
                            <td class="px-6 py-4 text-slate-900" x-text="task.recipient_phone || '-'"></td>
                            <td class="px-6 py-4 text-slate-900" x-text="task.delivery_town || '-'"></td>
                            <td class="px-6 py-4 text-slate-900" x-text="task.assigned_to || 'Unassigned'"></td>
                            <td class="px-6 py-4">
                                <template x-if="task.status === 'resolved' || task.outcome === 'deliver' || task.is_package_delivered">
                                    <span class="px-3 py-1 bg-[#10B981] text-white text-[11px] font-semibold rounded-full inline-block">Received</span>
                                </template>
                                <template x-if="task.outcome === 'unreachable'">
                                    <span class="px-3 py-1 bg-[#881337] text-white text-[11px] font-semibold rounded-full inline-block">Unreachable</span>
                                </template>
                                <template x-if="task.status === 'in_progress' || task.status === 'assigned'">
                                    <span class="px-3 py-1 bg-[#D97706] text-white text-[11px] font-semibold rounded-full inline-block">Processing</span>
                                </template>
                                <template x-if="task.status === 'pending' && task.outcome !== 'unreachable'">
                                    <span class="px-3 py-1 bg-slate-200 text-slate-700 text-[11px] font-semibold rounded-full inline-block">Pending</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" @click="openProcess(task)" class="p-1 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-slate-800 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M5 12a2 2 0 110-4 2 2 0 010 4zm7 0a2 2 0 110-4 2 2 0 010 4zm7 0a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 bg-white border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
            <div>
                Showing <span x-text="meta.from || 1"></span> to <span x-text="meta.to || tasks.length"></span> of <span x-text="meta.total || tasks.length"></span> tasks
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" @click="previousPage()" :disabled="meta.current_page <= 1" class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════ MODALS & SLIDE-OVER DRAWER ═══════════ --}}
    
    {{-- 1. Process Recipient Modal --}}
    <template x-teleport="body">
        <div x-show="processOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" x-transition.opacity @click.self="closeProcess()" @keydown.escape.window="closeProcess()">
            <div class="bg-white rounded-3xl p-6 max-w-xl w-full shadow-2xl relative space-y-4 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Process Recipient Task</h3>
                    <button type="button" @click="closeProcess()" class="p-1.5 text-slate-400 hover:text-slate-700 bg-slate-50 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="bg-orange-50/50 p-4 rounded-xl border border-orange-100/80">
                    <p class="text-xs font-bold uppercase text-orange-600" x-text="processTask?.shipment_number || 'Shipment'"></p>
                    <p class="text-base font-bold text-slate-900 mt-1" x-text="processTask?.recipient_name"></p>
                    <p class="text-xs text-slate-500" x-text="processTask?.recipient_phone"></p>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Call Outcome</label>
                        <select x-model="processForm.call_outcome" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-medium outline-none">
                            <option value="">Select Call Result</option>
                            <template x-for="opt in callOutcomeOptions" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Recipient Decision</label>
                        <select x-model="processForm.decision" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-medium outline-none">
                            <option value="">Select Decision</option>
                            <template x-for="opt in resolveOutcomeOptions" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label>
                        <textarea x-model="processForm.notes" rows="3" placeholder="Enter details..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs outline-none"></textarea>
                    </div>
                </div>

                <div x-show="processError" class="p-3 bg-red-50 text-red-600 text-xs rounded-xl" x-text="processError"></div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="closeProcess()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium">Cancel</button>
                    <button type="button" @click="submitProcess()" :disabled="processSubmitting" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-medium shadow-sm">
                        <span x-text="processSubmitting ? 'Saving...' : 'Save Decision'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- 2. Agents Performance SIDE SLIDE-OVER DRAWER --}}
    <template x-teleport="body">
        <div x-show="workerStatsOpen" x-cloak class="fixed inset-0 z-[9999] overflow-hidden" 
             x-transition:enter="transition opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            
            {{-- Dark Overlay Backdrop --}}
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="workerStatsOpen = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl flex flex-col z-10"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                
                {{-- Header Blue Banner --}}
                <div class="bg-[#5c2d01] p-6 text-white relative shrink-0">
                    <button type="button" @click="workerStatsOpen = false" class="absolute top-5 right-5 p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <h2 class="text-xl font-bold tracking-tight">Agents Performance</h2>
                    <p class="text-xs text-blue-100 mt-1">Live tracking of agent call resolutions, active tasks, and performance.</p>
                </div>

                {{-- Side Slide Drawer Content --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-4 bg-slate-50/50">
                    <div x-show="workerStatsLoading" class="flex justify-center py-12">
                        <svg class="w-7 h-7 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </div>

                    <template x-if="!workerStatsLoading && workerStatsData.length === 0">
                        <div class="text-center py-12 text-slate-400 text-xs font-medium">No performance data recorded for agents.</div>
                    </template>

                    <template x-for="ws in workerStatsData" :key="ws.id">
                        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 border border-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0" x-text="getInitials(ws.name)"></div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900" x-text="ws.name"></h4>
                                        <p class="text-[11px] text-slate-500" x-text="(ws.resolved || 0) + ' tasks resolved'"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-emerald-600" x-text="ws.resolved + ' Done'"></span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 bg-slate-50 rounded-xl p-2.5 text-center border border-slate-100">
                                <div>
                                    <p class="text-[10px] text-slate-400 uppercase font-semibold">Assigned</p>
                                    <p class="text-xs font-bold text-slate-800 mt-0.5" x-text="ws.total_assigned || 0"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 uppercase font-semibold">Pending</p>
                                    <p class="text-xs font-bold text-amber-600 mt-0.5" x-text="ws.pending || 0"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 uppercase font-semibold">Callbacks</p>
                                    <p class="text-xs font-bold text-rose-600 mt-0.5" x-text="ws.callbacks_due || 0"></p>
                                </div>
                            </div>
                        </div>
                    </template>
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
        cfg: {},
        tasks: [],
        meta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        loading: false,
        search: '',
        _searchTimeout: null,
        statusFilter: '',
        workerFilter: '',
        showFilters: false,

        // Date Filter State
        dateRange: 'today',
        dateRangeLabel: 'Today',
        dateOptions: [
            { value: 'today', label: 'Today' },
            { value: 'yesterday', label: 'Yesterday' },
            { value: 'this_week', label: 'This Week' },
            { value: 'this_month', label: 'This Month' },
            { value: 'all', label: 'All Time' },
        ],

        localStats: {
            unassigned: {{ $stats['unassigned'] ?? 0 }},
            assigned: {{ $stats['assigned'] ?? 0 }},
            in_progress: {{ $stats['in_progress'] ?? 0 }},
            callbacks_due: {{ $stats['callbacks_due'] ?? 0 }},
            resolved_today: {{ $stats['resolved_today'] ?? 0 }},
            total_amount: '670',
        },

        statusOptions: [
            { value: '', label: 'All Statuses' },
            { value: 'pending', label: 'Pending' },
            { value: 'assigned', label: 'Assigned' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'resolved', label: 'Resolved' },
        ],

        callOutcomeOptions: [
            { value: 'answered', label: 'Answered' },
            { value: 'no_answer', label: 'No Answer' },
            { value: 'busy', label: 'Busy' },
            { value: 'wrong_number', label: 'Wrong Number' },
            { value: 'voicemail', label: 'Voicemail' },
        ],

        resolveOutcomeOptions: [
            { value: 'deliver', label: 'Wants Delivery' },
            { value: 'self_pickup', label: 'Will Pick Up' },
            { value: 'unreachable', label: 'Unreachable' },
            { value: 'wrong_number', label: 'Wrong Number' },
            { value: 'callback', label: 'Schedule Callback' },
        ],

        processOpen: false,
        processTask: null,
        processSubmitting: false,
        processError: '',
        processForm: { call_outcome: '', decision: '', notes: '', callback_at: '' },

        workerStatsOpen: false,
        workerStatsLoading: false,
        workerStatsData: [],

        init() {
            const el = this.$root || document.querySelector('[data-contact-config]');
            const scriptConfig = document.getElementById('contact-queue-config')?.textContent;
            this.cfg = scriptConfig ? JSON.parse(scriptConfig) : JSON.parse(el.dataset.contactConfig || '{}');
            this.fetchData(1);
        },

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

        // Helper for Agent Avatars
        getInitials(name) {
            if (!name) return 'AG';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        },

        // Date Filter Selection
        setDateRange(value, label) {
            this.dateRange = value;
            this.dateRangeLabel = label;
            this.fetchData(1);
        },

        fetchData(page) {
            this.loading = true;
            const params = new URLSearchParams({ page, per_page: this.meta.per_page });
            if (this.search) params.set('search', this.search);
            if (this.statusFilter) params.set('status', this.statusFilter);
            if (this.workerFilter) params.set('worker_id', this.workerFilter);
            if (this.dateRange) params.set('date_range', this.dateRange);

            fetch(this.cfg.dataUrl + '?' + params.toString(), { headers: this.headers() })
                .then(r => r.json())
                .then(json => {
                    this.tasks = (json.data || []).map((task) => ({
                        ...task,
                        tracking_code: task.tracking_code || task.tracking_number || '',
                        delivery_town: task.delivery_town || task.town || '',
                        assigned_to: task.assigned_to || task.assigned_to_name || '',
                    }));
                    this.meta = json.meta || this.meta;
                    if (json.stats) this.localStats = { ...this.localStats, ...json.stats };
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },

        onSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.fetchData(1), 350);
        },

        applyFilters() { this.fetchData(1); },
        clearFilters() { this.statusFilter = ''; this.workerFilter = ''; this.fetchData(1); },
        previousPage() { if ((this.meta.current_page || 1) > 1) this.fetchData(this.meta.current_page - 1); },
        nextPage() { if ((this.meta.current_page || 1) < (this.meta.last_page || 1)) this.fetchData(this.meta.current_page + 1); },

        openProcess(task) {
            this.processTask = task;
            this.processForm = { call_outcome: '', decision: '', notes: '', callback_at: '' };
            this.processError = '';
            this.processOpen = true;
        },

        closeProcess() {
            this.processOpen = false;
            this.processTask = null;
        },

        async submitProcess() {
            if (!this.processTask || !this.processForm.call_outcome) return;
            this.processSubmitting = true;
            this.processError = '';

            try {
                const logUrl = this.cfg.logCallUrl.replace('__TASK__', this.processTask.id);
                await fetch(logUrl, {
                    method: 'POST',
                    headers: this.headers(true),
                    body: JSON.stringify({ call_outcome: this.processForm.call_outcome, notes: this.processForm.notes }),
                });

                if (this.processForm.decision) {
                    const resolveUrl = this.cfg.resolveUrl.replace('__TASK__', this.processTask.id);
                    await fetch(resolveUrl, {
                        method: 'POST',
                        headers: this.headers(true),
                        body: JSON.stringify({ outcome: this.processForm.decision, notes: this.processForm.notes }),
                    });
                }

                this.closeProcess();
                this.fetchData(this.meta.current_page);
            } catch (e) {
                this.processError = e.message || 'Failed to save details.';
            } finally {
                this.processSubmitting = false;
            }
        },

        async openWorkerStats() {
            this.workerStatsData = [];
            this.workerStatsLoading = true;
            this.workerStatsOpen = true;
            try {
                const res = await fetch(this.cfg.workerStatsUrl + '?date_range=' + this.dateRange, { headers: this.headers() });
                const json = await res.json();
                this.workerStatsData = json.data || [];
            } catch (e) {}
            this.workerStatsLoading = false;
        },

        exportData(type) {
            if (type === 'print') { window.print(); return; }
            const headers = ['Tracking', 'Recipient', 'Contact', 'Location', 'Claimed by', 'Status'];
            const rows = this.tasks.map(t => [t.tracking_code, t.recipient_name, t.recipient_phone, t.delivery_town, t.assigned_to, t.status]);
            const csv = [headers, ...rows].map(e => e.join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `recipient-queue-${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();
        }
    };
}
</script>
@endpush