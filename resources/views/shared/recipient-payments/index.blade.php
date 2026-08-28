@extends('warehouse.layouts.app')

@section('title', 'Recipient Payments')
@section('breadcrumb-parent', 'Finance')
@section('breadcrumb-current', 'Recipient Payments')

@section('content')
@php
    $rpConfig = [
        'dataUrl' => route($routePrefix . '.data'),
        'dataExportUrl' => route($routePrefix . '.data.export'),
        'reportsUrl' => route($routePrefix . '.reports'),
        'locationSearchUrl' => route($routePrefix . '.locations.search'),
        'walletsUrl' => route($routePrefix . '.wallets'),
        'walletExportUrl' => route($routePrefix . '.wallets.export'),
        'walletStoreUrl' => route($routePrefix . '.wallets.store'),
        'walletUpdateUrl' => route($routePrefix . '.wallets.update', ['wallet' => '__WALLET__']),
        'walletDeleteUrl' => route($routePrefix . '.wallets.delete', ['wallet' => '__WALLET__']),
        'walletStatusUrl' => route($routePrefix . '.wallets.status', ['wallet' => '__WALLET__']),
        'sessionsUrl' => route($routePrefix . '.sessions'),
        'sessionExportUrl' => route($routePrefix . '.sessions.export'),
        'sessionStoreUrl' => route($routePrefix . '.sessions.store'),
        'sessionCloseUrl' => route($routePrefix . '.sessions.close', ['session' => '__SESSION__']),
        'bulkAssignUrl' => route($routePrefix . '.bulk-assign'),
        'scanUrl' => route($routePrefix . '.scan'),
        'groupLogCallUrl' => route($routePrefix . '.groups.log-call'),
        'groupUpdateDetailsUrl' => route($routePrefix . '.groups.update-details'),
        'groupMarkPaidUrl' => route($routePrefix . '.groups.mark-paid'),
        'assignUrl' => route($routePrefix . '.assign', ['task' => '__TASK__']),
        'releaseUrl' => route($routePrefix . '.release', ['task' => '__TASK__']),
        'logCallUrl' => route($routePrefix . '.log-call', ['task' => '__TASK__']),
        'feeUrl' => route($routePrefix . '.fee', ['task' => '__TASK__']),
        'markPaidUrl' => route($routePrefix . '.mark-paid', ['task' => '__TASK__']),
        'overrideUrl' => route($routePrefix . '.override', ['task' => '__TASK__']),
    ];
@endphp

<div class="max-w-[1600px] mx-auto p-4 sm:p-6 lg:p-8 space-y-6"
     data-config="{{ json_encode($rpConfig) }}"
     data-workers="{{ json_encode($workers) }}"
     data-wallets="{{ json_encode($wallets) }}"
     data-warehouses="{{ json_encode($warehouses) }}"
     data-current-user-id="{{ $currentUserId }}"
     data-can-manage-wallets="{{ json_encode($canManageWallets) }}"
     data-agent-only="{{ json_encode($isAgentOnly ?? false) }}"
     x-data="recipientPaymentsPage()"
     x-init="init()">

    {{-- ═══════════ HEADER & NOTICES ═══════════ --}}
    <div x-show="!agentOnly" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Recipient Payments</h1>
                
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
            <p class="text-slate-500 text-sm mt-1">Monitor active fee negotiations and daily payment collections.</p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button type="button" @click="openScanModal()" class="px-5 py-3 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl shadow-sm transition-all active:scale-95 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/></svg>
                Scan Package
            </button>
            <button type="button" @click="reconcileDrawerOpen = true" class="px-5 py-3 bg-[#ea580c] hover:bg-[#c2410c] text-white text-sm font-semibold rounded-xl shadow-md shadow-orange-500/20 transition-all active:scale-95 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Reconcile Wallets
            </button>
        </div>
    </div>

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

    {{-- ═══════════ STATS CARDS ═══════════ --}}
    <div x-show="!agentOnly" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Queue Total</span>
            <span class="text-4xl font-normal text-slate-900" x-text="meta.total || 0">0</span>
        </div>
        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Payments</span>
            <span class="text-4xl font-normal text-slate-900" x-text="summary.pending || 0">0</span>
        </div>
        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Paid Packages</span>
            <span class="text-4xl font-normal text-slate-900" x-text="summary.paid || 0">0</span>
        </div>
        <div class="bg-[#FFFBF8] border border-[#F6E8E1] rounded-2xl p-5 flex flex-col justify-between shadow-sm h-32">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Expected Value</span>
            <span class="text-4xl font-normal text-slate-900" x-text="'GH₵ ' + formatMoney(summary.expected)">GH₵ 0.00</span>
        </div>
    </div>

    {{-- ═══════════ TABS ═══════════ --}}
    <div x-show="!agentOnly" class="flex flex-wrap gap-2 border-b border-slate-200 pb-1">
        <button type="button" @click="setTab('all')" :class="tabLinkClass('all')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors">All Payments</button>
        <button type="button" @click="setTab('local_delivery')" :class="tabLinkClass('local_delivery')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors">Local Delivery</button>
        <button type="button" @click="setTab('warehouse_transfer')" :class="tabLinkClass('warehouse_transfer')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors">Warehouse Transfer</button>
        <button type="button" @click="setTab('mine')" :class="tabLinkClass('mine')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors">Assigned to Me</button>
        <button type="button" @click="setTab('sessions')" :class="tabLinkClass('sessions')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors">Agent Sessions</button>
        @if($canManageWallets)
            <button type="button" @click="setTab('wallets')" :class="tabLinkClass('wallets')" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors">Manage Wallets</button>
        @endif
    </div>

    {{-- ═══════════ MAIN TABLE CONTAINER ═══════════ --}}
    <div x-show="!agentOnly && !['sessions', 'wallets'].includes(activeTab)" x-cloak class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
        
        {{-- Search & Action Bar --}}
        <div class="p-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-b border-slate-100">
            <div class="relative w-full sm:w-80">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @input="onSearch()" placeholder="Search Tracking, Recipient, Phone..."
                       class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-10 pr-4 py-2 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all">
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                <select x-model="taskStatusFilter" @change="loadData(1)" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none focus:border-orange-400">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In progress</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                </select>

                <select x-show="activeTab !== 'mine'" x-model="taskAgentFilter" @change="loadData(1)" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none focus:border-orange-400">
                    <option value="">All agents</option>
                    <template x-for="worker in workers" :key="worker.id">
                        <option :value="worker.id" x-text="worker.name"></option>
                    </template>
                </select>

                <button type="button" @click="exportTasks('csv')" class="px-4 py-2 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 002 3h12a3 3 0 002-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </button>
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
                        @if($canAssign) <th x-show="activeTab !== 'mine'" class="px-4 py-3.5 w-10"></th> @endif
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Package / Tracking</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Recipient Info</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Delivery Fee</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Payment Status</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Call Result</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700">Wallet</th>
                        <th class="px-6 py-3.5 text-xs font-semibold text-slate-700 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-800 bg-white">
                    <template x-if="!loading && tasks.length === 0">
                        <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400">No payment tasks match the current filters.</td></tr>
                    </template>

                    <template x-for="task in tasks" :key="task.id">
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            @if($canAssign)
                            <td x-show="activeTab !== 'mine'" class="px-4 py-4">
                                <input type="checkbox" :value="task.id" x-model="selectedTaskIds" class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                            </td>
                            @endif
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900" x-text="task.is_group ? task.description : (task.description || 'Package')"></p>
                                <p class="font-mono text-slate-500 text-[11px] mt-0.5" x-text="task.is_group ? (task.batch_number || 'No batch') : (task.tracking_code || '')"></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900" x-text="task.recipient_name || 'No recipient'"></p>
                                <p class="text-slate-500 text-[11px] mt-0.5" x-text="`${task.recipient_phone || '-'} · ${task.delivery_town || '-'}`"></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-black text-slate-900" x-text="task.fee_amount !== null ? 'GHS ' + formatMoney(task.fee_amount) : '-'"></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full inline-block" :class="feeStatusClass(task)" x-text="feeStatusLabel(task)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800" x-text="task.call_result_label || '-'"></p>
                                <p x-show="task.last_call_at" class="mt-0.5 text-[10px] text-slate-400" x-text="formatDateTime(task.last_call_at)"></p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-700" x-text="primaryWallet()?.name || '-'"></p>
                                <p class="text-slate-500 text-[10px]" x-text="primaryWallet()?.phone_number || ''"></p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" x-show="receiptUrlFor(task)" @click.stop="openTaskReceipt(task)" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-bold text-orange-700 hover:bg-orange-100">Receipt</button>
                                    <button type="button" @click.stop="openTask(task)" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800" x-text="task.is_group ? 'Process Group' : 'Process'"></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Bulk Assign Footer (If Active) --}}
        @if($canAssign)
        <div x-show="activeTab !== 'mine' && selectedTaskIds.length > 0" x-cloak class="px-6 py-3 bg-orange-50 border-t border-orange-100 flex items-center justify-between">
            <span class="text-xs font-bold text-orange-800" x-text="`${selectedTaskIds.length} tasks selected`"></span>
            <div class="flex items-center gap-2">
                <select x-model="bulkAssignUserId" class="rounded-lg border border-orange-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 outline-none">
                    <option value="">Assign to...</option>
                    <template x-for="worker in workers" :key="worker.id">
                        <option :value="worker.id" x-text="worker.name"></option>
                    </template>
                </select>
                <button type="button" @click="bulkAssign()" :disabled="!bulkAssignUserId" class="rounded-lg bg-orange-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-orange-700 disabled:opacity-50">Assign</button>
            </div>
        </div>
        @endif

        {{-- Pagination Footer --}}
        <div class="px-6 py-4 bg-white border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
            <div>
                Showing <span x-text="meta.from || 1"></span> to <span x-text="meta.to || tasks.length"></span> of <span x-text="meta.total || tasks.length"></span> tasks
            </div>
            <div class="flex items-center gap-1">
                <button type="button" @click="previousPage()" :disabled="meta.current_page <= 1" class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" @click="nextPage()" :disabled="meta.current_page >= meta.last_page" class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30 disabled:hover:bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════ SESSIONS TAB CONTENT ═══════════ --}}
    <div x-show="!agentOnly && activeTab === 'sessions'" x-cloak class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Agent Sessions</h2>
            <button type="button" @click="openStartSession()" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                Start Session
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[900px]">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Agent</th>
                        <th class="px-4 py-3 font-semibold">Wallet</th>
                        <th class="px-4 py-3 font-semibold">Opening</th>
                        <th class="px-4 py-3 font-semibold">Expected</th>
                        <th class="px-4 py-3 font-semibold">Variance</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="session in sessions" :key="session.id">
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-slate-900" x-text="session.agent || '-'"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="session.wallet || '-'"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="'GHS ' + formatMoney(session.opening_balance)"></td>
                            <td class="px-4 py-3 font-bold text-slate-900" x-text="session.expected_closing_balance === null ? '-' : 'GHS ' + formatMoney(session.expected_closing_balance)"></td>
                            <td class="px-4 py-3 font-bold" :class="Number(session.variance || 0) === 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="session.variance === null ? '-' : 'GHS ' + formatMoney(session.variance)"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-md text-[10px] font-bold" :class="session.status === 'open' ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-600'" x-text="statusLabel(session.status)"></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button x-show="session.status === 'open'" type="button" @click="openCloseSession(session)" class="text-xs font-bold text-slate-900 hover:text-orange-600">Close</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════ WALLETS TAB CONTENT ═══════════ --}}
    @if($canManageWallets)
    <div x-show="!agentOnly && activeTab === 'wallets'" x-cloak class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Manage Wallets</h2>
            <button type="button" @click="openWalletModal()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/></svg>
                Add Wallet
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[900px]">
                <thead class="bg-slate-50 border-b border-slate-100 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Wallet Name</th>
                        <th class="px-4 py-3 font-semibold">Provider / Phone</th>
                        <th class="px-4 py-3 font-semibold">Assigned Agents</th>
                        <th class="px-4 py-3 font-semibold">Recorded Amount</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="wallet in walletRows" :key="wallet.id">
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-slate-900" x-text="wallet.name"></td>
                            <td class="px-4 py-3">
                                <p class="text-slate-900 font-medium" x-text="wallet.phone_number"></p>
                                <p class="text-slate-500 text-[10px]" x-text="wallet.provider"></p>
                            </td>
                            <td class="px-4 py-3 text-slate-600" x-text="assignedAgentNames(wallet)"></td>
                            <td class="px-4 py-3 font-bold text-slate-900">GHS <span x-text="formatMoney(wallet.recorded_amount)"></span></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-md text-[10px] font-bold" :class="wallet.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" x-text="wallet.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" @click="openWalletModal(wallet)" class="text-xs font-bold text-slate-900 hover:text-orange-600 mr-2">Edit</button>
                                <button x-show="wallet.can_delete" type="button" @click="confirmWalletDelete(wallet)" class="text-xs font-bold text-rose-600 hover:text-rose-800">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══════════ RECONCILE WALLETS DRAWER (SLIDE-OVER) ═══════════ --}}
    <template x-teleport="body">
        <div x-show="reconcileDrawerOpen" x-cloak class="fixed inset-0 z-[9999] overflow-hidden" 
             x-transition:enter="transition opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            
            {{-- Dark Overlay Backdrop --}}
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="reconcileDrawerOpen = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl flex flex-col z-10"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                
                {{-- Header Blue Banner --}}
                <div class="bg-[#2563EB] p-6 text-white relative shrink-0">
                    <button type="button" @click="reconcileDrawerOpen = false" class="absolute top-5 right-5 p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <h2 class="text-xl font-bold tracking-tight">Agent Wallet Reconciliation</h2>
                    <p class="text-sm text-blue-100 mt-1">Verify cash handovers from agents at the end of their shift.</p>
                </div>

                {{-- Side Slide Drawer Content --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-6 bg-slate-50/50">
                    
                    {{-- Total Unreconciled Box --}}
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Total Unreconciled Cash Today</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-sm font-bold text-slate-500">GHS</span>
                            <span class="text-4xl font-extrabold text-slate-900" x-text="formatMoney(agentRecordedToday())">0.00</span>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-black uppercase text-slate-400 mb-3 tracking-wider">Agents Holding Cash</p>
                        
                        <template x-if="openSessions().length === 0">
                            <p class="text-sm text-slate-500 font-medium">All agent wallets are clear.</p>
                        </template>

                        <div class="space-y-3">
                            <template x-for="session in openSessions()" :key="session.id">
                                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-600 font-bold text-sm flex items-center justify-center shrink-0" x-text="getInitials(session.agent)"></div>
                                            <div>
                                                <h4 class="text-sm font-bold text-slate-900" x-text="session.agent || 'Agent'"></h4>
                                                <p class="text-xs text-slate-500" x-text="session.wallet || 'Wallet'"></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[10px] font-bold text-slate-400">GHS</span>
                                            <span class="text-base font-bold text-slate-900" x-text="formatMoney(session.expected_closing_balance || session.opening_balance)"></span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" @click="openCloseSession(session); reconcileDrawerOpen = false" class="flex-1 bg-blue-50 text-blue-700 hover:bg-blue-100 py-2 rounded-xl text-xs font-bold transition-colors">Clear Balance</button>
                                        <button type="button" class="flex-1 border border-slate-200 text-slate-600 hover:bg-slate-50 py-2 rounded-xl text-xs font-bold transition-colors">Report Shortage</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>


    {{-- ═══════════ MODALS (PRESERVED FROM OLD CODE) ═══════════ --}}
    
    {{-- 1. Process Task Modal --}}
    <template x-teleport="body">
    <div x-show="taskModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] overflow-y-auto" style="display:none">
        <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-sm" @click="taskModalOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-3 sm:p-4">
            <div @click.stop class="relative flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow-2xl">
                <div class="relative shrink-0 rounded-t-2xl border-b border-slate-100 bg-white px-3 py-3 sm:px-6 sm:py-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2.5 sm:gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600 sm:h-12 w-12">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-2.2 0-4 1.2-4 2.8s1.8 2.8 4 2.8 4 1.2 4 2.8S14.2 19.2 12 19.2m0-11.2V6m0 13.2V21M5 12H3m18 0h-2"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-black leading-tight text-slate-900 sm:text-xl">Process Payment</h3>
                                <p class="mt-1 text-xs font-medium leading-5 text-slate-500 sm:text-sm">
                                    <span x-text="activeTask ? groupLabel(activeTask.payment_group) : 'Recipient Payment'"></span>
                                    <span> - Verify packages and record payment.</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="taskModalOpen = false" class="shrink-0 rounded-xl bg-slate-50 p-2 text-slate-400 hover:text-slate-700">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div class="max-h-[calc(100vh-220px)] min-h-0 flex-1 space-y-5 overflow-y-auto bg-slate-50 px-4 py-5 sm:px-6 sm:py-6">
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-black text-slate-900">Recipient Details</h4>
                            <div class="flex shrink-0 items-center gap-2">
                                <button type="button" x-show="!recipientEditing" @click="recipientEditing = true" class="px-3 py-1.5 text-xs font-bold text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50">Edit</button>
                                <button type="button" x-show="recipientEditing" @click="saveRecipientContactDetails()" class="px-3 py-1.5 text-xs font-bold text-orange-700 border border-orange-200 bg-orange-50 rounded-lg">Save</button>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-bold text-slate-500">Name</label>
                                <div class="w-full rounded-xl bg-slate-50 px-3 py-2 text-sm font-bold text-slate-900 border border-slate-100" x-text="singleModalTask()?.original_recipient_name || singleModalTask()?.recipient_name || 'No recipient'"></div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-500">Phone</label>
                                <div x-show="!recipientEditing" class="w-full rounded-xl bg-slate-50 px-3 py-2 text-sm font-bold text-slate-900 border border-slate-100" x-text="taskForm.recipient_phone || '-'"></div>
                                <input x-show="recipientEditing" x-model="taskForm.recipient_phone" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-400">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-500">Location</label>
                                <div x-show="!recipientEditing" class="w-full rounded-xl bg-slate-50 px-3 py-2 text-sm font-bold text-slate-900 border border-slate-100" x-text="taskForm.delivery_town || '-'"></div>
                                <input x-show="recipientEditing" x-model="taskForm.delivery_town" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-400">
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
                        <h4 class="text-sm font-black text-slate-900 mb-4">Payment Info</h4>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-500">Delivery Fee (GHS) *</label>
                                <input type="number" step="0.01" x-model="taskForm.amount" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-lg font-black text-slate-900 outline-none focus:border-orange-400">
                            </div>
                            <div x-show="activeWallets().length !== 1">
                                <label class="mb-1 block text-xs font-bold text-slate-500">Wallet *</label>
                                <select x-model="taskForm.payment_wallet_id" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-orange-400">
                                    <option value="">Select wallet</option>
                                    <template x-for="wallet in activeWallets()" :key="wallet.id">
                                        <option :value="wallet.id" x-text="walletLabel(wallet)"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-500">Call Result *</label>
                                <select x-model="taskForm.outcome" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-orange-400">
                                    <option value="answered">Answered</option>
                                    <option value="no_answer">No answer</option>
                                    <option value="callback">Call back later</option>
                                    <option value="wrong_number">Wrong number</option>
                                    <option value="payment_promised">Pay later</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-500">MoMo Reference</label>
                                <input x-model="taskForm.payment_reference" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-orange-400" placeholder="Optional">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-bold text-slate-500">Notes</label>
                                <textarea x-model="taskForm.notes" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-400" placeholder="Any details..."></textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-slate-100 bg-white px-4 py-4">
                    <button type="button" x-show="modalCanRelease()" @click="releaseActiveTask()" class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-100">Release</button>
                    <span x-show="!modalCanRelease()"></span>
                    <div class="flex gap-2">
                        <button type="button" @click="saveRecipientDetails()" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Save Info</button>
                        <button type="button" @click="markPaid()" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800" x-text="paymentSubmitLabel()"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>

    {{-- 2. Scan Package Modal --}}
    <template x-teleport="body">
    <div x-show="scanModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
        <div @click.stop class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900">Scan Package</h3>
                        <p class="mt-1 text-sm text-slate-500">Scan the printed label to load the recipient payment task.</p>
                    </div>
                </div>
                <button type="button" @click="closeScanModal()" class="rounded-xl bg-slate-50 p-2 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-5">
                <div class="relative aspect-video overflow-hidden rounded-2xl bg-slate-900 flex items-center justify-center">
                    <video x-ref="scanVideo" class="hidden h-full w-full object-cover" playsinline muted></video>
                    <canvas x-ref="scanCanvas" class="hidden"></canvas>
                    <button x-show="!scannerActive" type="button" @click="startScanner()" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-bold">Start Camera</button>
                </div>
                <p x-show="scannerStatus" class="mt-2 text-center text-xs text-slate-500" x-text="scannerStatus"></p>
                <div x-show="scanModalMessage" class="mt-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-600" x-text="scanModalMessage"></div>
                
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <form class="flex gap-2" @submit.prevent="scanPackage()">
                        <input type="text" x-model="scanCode" x-ref="scanCodeInput" placeholder="Enter tracking code manually" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-400">
                        <button type="submit" x-show="scanCode.trim()" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white">Load</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </template>

    {{-- 3. Close Session Modal --}}
    <template x-teleport="body">
    <div x-show="closeSessionModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
        <div @click.stop class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl p-6 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">Close Payment Session</h3>
                <button type="button" @click="closeSessionModalOpen = false" class="p-1.5 rounded-full bg-slate-50 text-slate-400 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Closing Balance (GHS)</label>
                <input type="number" step="0.01" x-model="closeSessionForm.closing_balance" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-lg font-black text-slate-900 outline-none focus:border-orange-400">
                <p class="mt-1 text-xs text-slate-500">Calculated from opening balance + recorded payments.</p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Notes (optional)</label>
                <textarea x-model="closeSessionForm.notes" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-400"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" @click="closeSessionModalOpen = false" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600">Cancel</button>
                <button type="button" @click="closeSession()" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">Submit Closure</button>
            </div>
        </div>
    </div>
    </template>

    {{-- 4. Start Session Modal --}}
    <template x-teleport="body">
    <div x-show="startSessionModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
        <div @click.stop class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl p-6 space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">Start Session</h3>
                <button type="button" @click="startSessionModalOpen = false" class="p-1.5 rounded-full bg-slate-50 text-slate-400 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div x-show="activeWallets().length > 1">
                <label class="block text-xs font-bold text-slate-500 mb-1">Wallet</label>
                <select x-model="sessionForm.wallet_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-400">
                    <option value="">Select wallet</option>
                    <template x-for="wallet in activeWallets()" :key="wallet.id">
                        <option :value="wallet.id" x-text="walletLabel(wallet)"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Opening Balance (GHS)</label>
                <input type="number" step="0.01" x-model="sessionForm.opening_balance" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-lg font-black text-slate-900 outline-none focus:border-orange-400">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" @click="startSessionModalOpen = false" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600">Cancel</button>
                <button type="button" @click="startSession()" class="px-4 py-2 bg-orange-600 text-white rounded-xl text-xs font-bold">Start</button>
            </div>
        </div>
    </div>
    </template>

    {{-- 5. View Photo Modal --}}
    <template x-teleport="body">
    <div x-show="vendorPhotoModalOpen" x-cloak x-transition.opacity @click="closePhotoLightbox()" class="fixed inset-0 z-[130] flex cursor-zoom-out items-center justify-center bg-black/85 p-8 backdrop-blur-sm" style="display:none">
        <button type="button" @click.stop="closePhotoLightbox()" class="absolute right-4 top-4 z-20 rounded-full bg-white/20 p-2 text-white hover:bg-white/40 transition">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img @click.stop :src="activePackagePhoto()?.url" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl">
    </div>
    </template>
    
    {{-- 6. Wallet Management Modals --}}
    @if($canManageWallets)
    <template x-teleport="body">
    <div x-show="walletModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center overflow-y-auto bg-black/55 p-4 backdrop-blur-sm" @click="walletAgentsOpen = false" style="display:none">
        <div @click.stop class="w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl relative space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900" x-text="walletModalMode === 'edit' ? 'Edit Payment Wallet' : 'Add Payment Wallet'"></h3>
                <button type="button" @click="walletModalOpen = false" class="p-1.5 rounded-full bg-slate-50 text-slate-400 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-3">
                <input x-model="walletForm.name" placeholder="Wallet Name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-400">
                <input x-model="walletForm.phone_number" placeholder="Phone Number" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-orange-400">
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" @click="walletModalOpen = false" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600">Cancel</button>
                <button type="button" @click="saveWallet()" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold">Save Wallet</button>
            </div>
        </div>
    </div>
    </template>
    
    <template x-teleport="body">
    <div x-show="confirmModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[120] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
        <div @click.stop class="w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl relative space-y-4">
            <h3 class="text-lg font-bold text-slate-900">Delete Wallet</h3>
            <p class="text-sm text-slate-500" x-text="confirmMessage"></p>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" @click="confirmModalOpen = false" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600">Cancel</button>
                <button type="button" @click="deleteWallet()" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold">Delete</button>
            </div>
        </div>
    </div>
    </template>
    @endif
</div>
@endsection

@push('scripts')
<script>
function recipientPaymentsPage() {
    return {
        config: {},
        currentUserId: null,
        canManageWallets: false,
        agentOnly: false,
        workers: [],
        wallets: [],
        walletRows: [],
        warehouses: [],
        tasks: [],
        sessions: [],
        
        // Modal States
        taskModalOpen: false,
        scanModalOpen: false,
        startSessionModalOpen: false,
        closeSessionModalOpen: false,
        walletModalOpen: false,
        vendorPhotoModalOpen: false,
        confirmModalOpen: false,
        confirmMessage: '',
        reconcileDrawerOpen: false,
        
        // Loading States
        loading: false,
        sessionLoading: false,
        walletLoading: false,
        
        // Metas
        meta: { total: 0, from: 0, to: 0, current_page: 1, last_page: 1, per_page: 20 },
        sessionMeta: { total: 0, from: 0, to: 0, current_page: 1, last_page: 1 },
        walletMeta: { total: 0, from: 0, to: 0, current_page: 1, last_page: 1 },
        summary: { pending: 0, paid: 0, expected: 0 },
        
        // Filters
        activeTab: 'all',
        search: '',
        taskStatusFilter: '',
        taskAgentFilter: '',
        taskPerPage: 20,
        taskSortBy: 'id',
        taskSortDirection: 'desc',
        
        // Date Range (Top Level)
        dateRange: 'today',
        dateRangeLabel: 'Today',
        dateOptions: [
            { value: 'today', label: 'Today' },
            { value: 'yesterday', label: 'Yesterday' },
            { value: 'this_week', label: 'This Week' },
            { value: 'this_month', label: 'This Month' },
            { value: 'all', label: 'All Time' },
        ],
        
        // Session Filters
        sessionPerPage: 25,
        sessionSortBy: 'started_at',
        sessionSortDirection: 'desc',
        
        // Selected Tasks
        selectedTaskIds: [],
        bulkAssignUserId: '',
        
        // Forms
        activeTask: null,
        recipientEditing: false,
        taskForm: { outcome: 'answered', notes: '', amount: '', payment_wallet_id: '', payment_reference: '', payment_receipt_name: '', recipient_phone: '', delivery_town: '', assigned_to_user_id: '', override_reason: '' },
        
        closingSession: null,
        closeSessionForm: { closing_balance: '', notes: '' },
        sessionForm: { wallet_id: '', warehouse_id: '', opening_balance: '' },
        
        walletModalMode: 'create',
        editingWallet: null,
        walletForm: { name: '', provider: 'MTN MoMo', phone_number: '', account_owner: '', warehouse_id: '', user_ids: [] },
        
        vendorPhotoPackage: null,
        activePhotoIndex: 0,
        
        // Scanners
        scanCode: '',
        scannerActive: false,
        scannerStatus: '',
        scanModalMessage: '',
        
        notice: { success: true, message: '' },
        _searchTimeout: null,

        init() {
            const el = this.$root || document.querySelector('[data-config]');
            this.config = JSON.parse(el.dataset.config || '{}');
            this.currentUserId = Number(el.dataset.currentUserId || 0) || null;
            this.canManageWallets = JSON.parse(el.dataset.canManageWallets || 'false');
            this.agentOnly = JSON.parse(el.dataset.agentOnly || 'false');
            this.workers = JSON.parse(el.dataset.workers || '[]');
            this.wallets = JSON.parse(el.dataset.wallets || '[]');
            this.warehouses = JSON.parse(el.dataset.warehouses || '[]');

            const params = new URLSearchParams(window.location.search);
            this.search = params.get('search') || this.search;
            this.activeTab = this.resolveInitialTab();
            
            this.loadActiveTab();
            if (this.activeTab !== 'sessions') this.loadSessions();
        },

        resolveInitialTab() {
            if (this.agentOnly) return 'mine';
            const params = new URLSearchParams(window.location.search);
            return params.get('tab') || 'all';
        },

        setTab(tab) {
            this.activeTab = tab;
            this.selectedTaskIds = [];
            this.loadActiveTab();
        },

        loadActiveTab() {
            if (this.activeTab === 'sessions') {
                this.loadSessions(1);
            } else if (this.activeTab === 'wallets' && this.canManageWallets) {
                this.loadWallets(1);
            } else {
                this.loadData(1);
            }
        },

        tabLinkClass(tab) {
            return this.activeTab === tab
                ? 'bg-orange-50 text-orange-700 border border-orange-200'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50';
        },

        // Parses dateRange to actual 'date_from' and 'date_to' for Laravel backend
        getDateRangeParams() {
            const today = new Date();
            const pad = v => String(v).padStart(2, '0');
            const format = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            if (this.dateRange === 'today') {
                return { date_from: format(today), date_to: format(today) };
            }
            if (this.dateRange === 'yesterday') {
                const y = new Date(today); y.setDate(today.getDate() - 1);
                return { date_from: format(y), date_to: format(y) };
            }
            if (this.dateRange === 'this_week') {
                const w = new Date(today); w.setDate(today.getDate() - today.getDay());
                return { date_from: format(w), date_to: format(today) };
            }
            if (this.dateRange === 'this_month') {
                const m = new Date(today.getFullYear(), today.getMonth(), 1);
                return { date_from: format(m), date_to: format(today) };
            }
            return { date_from: '', date_to: '' }; // 'all'
        },
        
        setDateRange(value, label) {
            this.dateRange = value;
            this.dateRangeLabel = label;
            this.loadActiveTab();
        },

        getInitials(name) {
            if (!name) return 'AG';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        },

        openSessions() {
            return (this.sessions || []).filter(session => session.status === 'open');
        },

        agentRecordedToday() {
            return this.openSessions().reduce((sum, session) => {
                return sum + Math.max(0, Number(session.expected_closing_balance || 0) - Number(session.opening_balance || 0));
            }, 0);
        },

        async loadData(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({ page, per_page: this.taskPerPage, sort: this.taskSortBy, direction: this.taskSortDirection });
            if (this.search) params.set('search', this.search);
            if (this.taskStatusFilter) params.set('status', this.taskStatusFilter);
            if (this.taskAgentFilter && this.activeTab !== 'mine') params.set('assigned_to_user_id', this.taskAgentFilter);
            if (this.activeTab === 'local_delivery' || this.activeTab === 'warehouse_transfer') params.set('group', this.activeTab);
            if (this.activeTab === 'mine') { params.set('assigned_to_me', '1'); params.set('group_by_recipient', '1'); }
            
            // Set dynamic dates for backend
            const dates = this.getDateRangeParams();
            if (dates.date_from) params.set('date_from', dates.date_from);
            if (dates.date_to) params.set('date_to', dates.date_to);

            try {
                const res = await fetch(`${this.config.dataUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.tasks = json.data || [];
                this.meta = json.meta || this.meta;
                this.recalculateSummary();
            } catch (e) {
                this.toast(false, 'Failed to load payments.');
            } finally {
                this.loading = false;
            }
        },

        async loadSessions(page = 1) {
            this.sessionLoading = true;
            const params = new URLSearchParams({ page, per_page: this.sessionPerPage });
            
            // Set dynamic dates for backend
            const dates = this.getDateRangeParams();
            if (dates.date_from) params.set('date_from', dates.date_from);
            if (dates.date_to) params.set('date_to', dates.date_to);

            try {
                const res = await fetch(`${this.config.sessionsUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.sessions = json.data || [];
                this.sessionMeta = json.meta || this.sessionMeta;
            } finally {
                this.sessionLoading = false;
            }
        },

        async loadWallets(page = 1) {
            this.walletLoading = true;
            const params = new URLSearchParams({ page, per_page: 25 });
            
            // Set dynamic dates for backend
            const dates = this.getDateRangeParams();
            if (dates.date_from) params.set('date_from', dates.date_from);
            if (dates.date_to) params.set('date_to', dates.date_to);

            try {
                const res = await fetch(`${this.config.walletsUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.walletRows = json.data || [];
                this.walletMeta = json.meta || this.walletMeta;
            } finally {
                this.walletLoading = false;
            }
        },

        recalculateSummary() {
            this.summary.pending = this.tasks.reduce((sum, t) => sum + (t.is_group ? Number(t.pending_count || 0) : (!['paid', 'waived', 'overridden'].includes(t.status) ? 1 : 0)), 0);
            this.summary.paid = this.tasks.reduce((sum, t) => sum + (t.is_group ? Number(t.paid_count || 0) : (t.status === 'paid' ? 1 : 0)), 0);
            this.summary.expected = this.tasks.reduce((sum, t) => sum + Number(t.fee_amount || 0), 0);
        },

        onSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.loadData(1), 300);
        },

        goPage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.loadData(page);
        },
        previousPage() { this.goPage(this.meta.current_page - 1); },
        nextPage() { this.goPage(this.meta.current_page + 1); },

        sessionPreviousPage() { if(this.sessionMeta.current_page > 1) this.loadSessions(this.sessionMeta.current_page - 1); },
        sessionNextPage() { if(this.sessionMeta.current_page < this.sessionMeta.last_page) this.loadSessions(this.sessionMeta.current_page + 1); },

        formatMoney(value) { return Number(value || 0).toFixed(2); },
        
        formatDateTime(value) {
            if (!value) return '-';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString(undefined, { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: true });
        },

        groupLabel(group) {
            if (group === 'mixed') return 'Mixed';
            return group === 'local_delivery' ? 'Local Delivery' : 'Warehouse Transfer';
        },

        feeStatusLabel(task) {
            if (task.status === 'paid' || task.fee_status === 'paid' || task.fee_status === 'cleared') return 'Paid';
            if (task.status === 'waived' || task.fee_status === 'waived') return 'Waived';
            if (task.status === 'overridden') return 'Override';
            if (task.fee_amount === null) return 'No fee set';
            return 'Due';
        },

        feeStatusClass(task) {
            const label = this.feeStatusLabel(task);
            if (label === 'Paid') return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            if (label === 'Waived' || label === 'Override') return 'bg-slate-100 text-slate-700 ring-slate-200';
            if (label === 'No fee set') return 'bg-amber-50 text-amber-700 ring-amber-200';
            return 'bg-orange-50 text-orange-700 ring-orange-200';
        },
        
        statusLabel(status) { return String(status || '').replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase()); },
        
        statusClass(status) {
            if (status === 'paid') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (status === 'waived' || status === 'overridden') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
            if (status === 'disputed' || status === 'failed') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200';
        },

        primaryWallet() {
            const assigned = this.activeWallets();
            return assigned[0] || null;
        },

        activeWallets() {
            return this.wallets.filter(w => w.is_active && (this.canManageWallets || (w.assigned_users || []).some(u => Number(u.id) === Number(this.currentUserId))));
        },

        walletLabel(wallet) { return `${wallet.name} - ${wallet.phone_number}`; },

        openTask(task) {
            this.activeTask = task;
            const activeWallets = this.activeWallets();
            this.taskForm = {
                outcome: 'answered', notes: task.notes || '', amount: task.fee_amount ?? '',
                payment_wallet_id: task.payment_wallet_id || (activeWallets.length === 1 ? activeWallets[0].id : ''),
                payment_reference: task.payment_reference || '', recipient_phone: task.recipient_phone || '',
                delivery_town: task.delivery_town || ''
            };
            this.recipientEditing = false;
            this.taskModalOpen = true;
        },

        modalTasks() {
            if (!this.activeTask) return [];
            return this.activeTask.is_group ? (this.activeTask.tasks || []) : [this.activeTask];
        },
        
        singleModalTask() {
            const tasks = this.modalTasks();
            return tasks.length === 1 ? tasks[0] : null;
        },

        modalCanRelease() { return this.modalTasks().some(task => task.can_release); },

        paymentSubmitLabel() {
            return this.activeTask && this.feeStatusLabel(this.activeTask) === 'Paid' ? 'Save Payment' : 'Mark Paid';
        },

        async markPaid() {
            if (!this.activeTask || !this.taskForm.amount || !this.taskForm.payment_wallet_id) {
                this.toast(false, 'Fill all required payment fields.'); return;
            }
            const payload = new FormData();
            this.modalTasks().forEach(task => payload.append('task_ids[]', task.id));
            payload.append('amount', this.taskForm.amount);
            payload.append('recipient_phone', this.taskForm.recipient_phone || '');
            payload.append('delivery_town', this.taskForm.delivery_town || '');
            payload.append('payment_wallet_id', this.taskForm.payment_wallet_id);
            payload.append('payment_reference', this.taskForm.payment_reference || '');
            payload.append('notes', this.taskForm.notes || '');
            payload.append('outcome', this.taskForm.outcome || 'answered');
            
            const json = await this.postForm(this.config.groupMarkPaidUrl, payload);
            this.toast(json.success, json.message || 'Payment updated.');
            if (json.success) { this.taskModalOpen = false; this.loadData(this.meta.current_page); this.loadSessions(); }
        },

        async releaseActiveTask() {
            let last = { success: true };
            for (const task of this.modalTasks().filter(t => t.can_release)) {
                last = await this.post(this.config.releaseUrl.replace('__TASK__', task.id), {});
                if (!last.success) break;
            }
            this.toast(last.success, last.message || 'Release updated.');
            if (last.success) { this.taskModalOpen = false; this.loadData(this.meta.current_page); }
        },

        async saveRecipientContactDetails() {
            const taskIds = this.modalTasks().map(t => t.id);
            const json = await this.post(this.config.groupUpdateDetailsUrl, {
                task_ids: taskIds, recipient_phone: this.taskForm.recipient_phone, delivery_town: this.taskForm.delivery_town
            });
            this.toast(json.success, json.message || 'Recipient details saved.');
            if (json.success) { this.recipientEditing = false; this.loadData(this.meta.current_page); }
        },

        async bulkAssign() {
            const json = await this.post(this.config.bulkAssignUrl, { task_ids: this.selectedTaskIds, user_id: this.bulkAssignUserId });
            this.toast(json.success, json.message || 'Assignment updated.');
            if (json.success) { this.selectedTaskIds = []; this.loadData(this.meta.current_page); }
        },

        openScanModal() { this.scanCode = ''; this.scanModalMessage = ''; this.scanModalOpen = true; },
        closeScanModal() { this.scanModalOpen = false; },

        async scanPackage() {
            if (!this.scanCode.trim()) return;
            const json = await this.post(this.config.scanUrl, { code: this.scanCode.trim() });
            if (!json.success) { this.scanModalMessage = json.message || 'Package not found.'; return; }
            this.closeScanModal();
            this.openTask(json.task);
        },

        openStartSession() {
            const activeWallets = this.activeWallets();
            this.sessionForm = { wallet_id: activeWallets.length === 1 ? activeWallets[0].id : '', warehouse_id: this.warehouses[0]?.id || '', opening_balance: '' };
            this.startSessionModalOpen = true;
        },

        async startSession() {
            if (!this.activeWallets().length) { this.toast(false, 'No wallet assigned.'); return; }
            const payload = { payment_wallet_id: this.sessionForm.wallet_id, warehouse_id: this.sessionForm.warehouse_id, opening_balance: this.sessionForm.opening_balance };
            const json = await this.post(this.config.sessionStoreUrl, payload);
            this.toast(json.success, json.message || 'Session started.');
            if (json.success) { this.startSessionModalOpen = false; this.loadSessions(1); }
        },

        openCloseSession(session) {
            this.closingSession = session;
            this.closeSessionForm = { closing_balance: session.expected_closing_balance !== null ? Number(session.expected_closing_balance).toFixed(2) : Number(session.opening_balance || 0).toFixed(2), notes: '' };
            this.closeSessionModalOpen = true;
        },

        async closeSession() {
            const json = await this.post(this.config.sessionCloseUrl.replace('__SESSION__', this.closingSession.id), this.closeSessionForm);
            this.toast(json.success, json.message || 'Session closed.');
            if (json.success) { this.closeSessionModalOpen = false; this.reconcileDrawerOpen = false; this.loadSessions(this.sessionMeta.current_page); }
        },

        receiptUrlFor(task) { return task?.payment_receipt_url || task?.tasks?.find(c => c.payment_receipt_url)?.payment_receipt_url || null; },
        
        openTaskReceipt(task) {
            const url = this.receiptUrlFor(task);
            if (!url) return;
            this.vendorPhotoPackage = { vendor_photos: [{ url }] };
            this.activePhotoIndex = 0;
            this.vendorPhotoModalOpen = true;
        },
        
        activePackagePhoto() { return (this.vendorPhotoPackage?.vendor_photos || [])[this.activePhotoIndex] || null; },
        closePhotoLightbox() { this.vendorPhotoModalOpen = false; this.vendorPhotoPackage = null; },

        openWalletModal(wallet = null) {
            this.walletModalMode = wallet ? 'edit' : 'create';
            this.editingWallet = wallet;
            this.walletForm = {
                name: wallet?.name || '', provider: wallet?.provider || 'MTN MoMo', phone_number: wallet?.phone_number || '',
                account_owner: wallet?.account_owner || '', warehouse_id: wallet?.warehouse_id || (this.warehouses.length === 1 ? this.warehouses[0].id : ''),
                user_ids: (wallet?.assigned_users || []).map(u => Number(u.id)),
            };
            this.walletModalOpen = true;
        },

        async saveWallet() {
            const payload = { ...this.walletForm };
            const url = this.walletModalMode === 'edit' ? this.config.walletUpdateUrl.replace('__WALLET__', this.editingWallet.id) : this.config.walletStoreUrl;
            const json = await this.post(url, payload);
            this.toast(json.success, json.message || 'Wallet saved.');
            if (json.success) { this.walletModalOpen = false; this.loadWallets(this.walletMeta.current_page); }
        },

        confirmWalletDelete(wallet) {
            this.confirmWallet = wallet;
            this.confirmMessage = `Delete ${wallet.name}?`;
            this.confirmModalOpen = true;
        },

        async deleteWallet() {
            const json = await this.post(this.config.walletDeleteUrl.replace('__WALLET__', this.confirmWallet.id), {});
            this.toast(json.success, json.message || 'Wallet deleted.');
            if (json.success) { this.confirmModalOpen = false; this.loadWallets(this.walletMeta.current_page); }
        },

        async post(url, payload) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                    body: JSON.stringify(payload),
                });
                return await res.json();
            } catch (e) { return { success: false, message: 'Request failed.' }; }
        },
        
        async postForm(url, payload) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                    body: payload,
                });
                return await res.json();
            } catch (e) { return { success: false, message: 'Request failed.' }; }
        },

        toast(success, message) {
            this.notice = { success, message };
            if (success) setTimeout(() => { this.notice.message = ''; }, 4000);
        },

        exportTasks(format) {
            const params = new URLSearchParams({ format, sort: this.taskSortBy, direction: this.taskSortDirection });
            if (this.search) params.set('search', this.search);
            if (this.taskStatusFilter) params.set('status', this.taskStatusFilter);
            
            // Apply Dynamic Dates to Export
            const dates = this.getDateRangeParams();
            if (dates.date_from) params.set('date_from', dates.date_from);
            if (dates.date_to) params.set('date_to', dates.date_to);

            window.location.href = `${this.config.dataExportUrl}?${params.toString()}`;
        }
    };
}
</script>
@endpush