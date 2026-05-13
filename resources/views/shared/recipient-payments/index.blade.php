<?php $__env->startSection('title', 'Recipient Payments'); ?>
<?php $__env->startSection('breadcrumb-parent', 'Finance'); ?>
<?php $__env->startSection('breadcrumb-current', 'Recipient Payments'); ?>

<?php $__env->startSection('content'); ?>
<?php
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
?>

<div class="space-y-6"
     data-config='<?php echo json_encode($rpConfig, 15, 512) ?>'
     data-workers='<?php echo json_encode($workers, 15, 512) ?>'
     data-wallets='<?php echo json_encode($wallets, 15, 512) ?>'
     data-warehouses='<?php echo json_encode($warehouses, 15, 512) ?>'
     data-current-user-id='<?php echo e($currentUserId); ?>'
     data-can-manage-wallets='<?php echo json_encode($canManageWallets, 15, 512) ?>'
     data-agent-only='<?php echo json_encode($isAgentOnly ?? false, 15, 512) ?>'
     x-data="recipientPaymentsPage()"
     x-init="init()">
    <div x-show="!agentOnly" class="rounded-3xl bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 p-6 shadow-2xl shadow-slate-900/20">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-500 text-white shadow-xl shadow-orange-500/30">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm5 4h4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-300">Back-office workflow</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-white">Recipient Payments</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        Call recipients, set negotiated delivery fees, record approved wallet payments, and reconcile agent sessions before transport or delivery dispatch.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:min-w-[520px]">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Queue</p>
                    <p class="mt-2 text-2xl font-bold text-white" x-text="meta.total || 0"></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Pending</p>
                    <p class="mt-2 text-2xl font-bold text-orange-300" x-text="summary.pending"></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Paid</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-300" x-text="summary.paid"></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Expected</p>
                    <p class="mt-2 text-2xl font-bold text-white">GHS <span x-text="formatMoney(summary.expected)"></span></p>
                </div>
            </div>
        </div>
    </div>

    <div x-show="notice.message" x-cloak x-transition
         class="fixed right-6 top-6 z-[200] flex w-[min(420px,calc(100vw-2rem))] items-center gap-3 rounded-2xl border px-5 py-3 text-sm font-semibold shadow-2xl"
         :class="notice.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800 shadow-emerald-900/10' : 'border-rose-200 bg-rose-50 text-rose-800 shadow-rose-900/10'"
         role="alert">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path x-show="notice.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <path x-show="!notice.success" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span x-text="notice.message"></span>
        <button type="button" class="ml-auto opacity-60 hover:opacity-100" @click="notice.message = ''">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div x-show="agentOnly" x-cloak class="space-y-5">
        <div class="grid gap-4 lg:grid-cols-[1fr_340px]">
            <section class="overflow-hidden rounded-3xl border border-orange-100 bg-white shadow-lg shadow-slate-300/30">
                <div class="border-b border-orange-100 bg-gradient-to-r from-orange-50 to-white px-4 py-3 sm:px-5 sm:py-4">
                    <div class="flex items-start justify-between gap-2 sm:gap-3">
                        <div class="flex min-w-0 items-start gap-2.5 sm:gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-600 text-white shadow-lg shadow-orange-500/25 sm:h-12 sm:w-12 sm:rounded-2xl">
                                <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-base font-black leading-tight text-slate-900 sm:text-xl">Process Package Payment</h1>
                                <p class="mt-0.5 text-xs font-medium leading-5 text-slate-500 sm:mt-1 sm:text-sm">Scan a package label to call, confirm details, and record payment.</p>
                            </div>
                        </div>
                        <span class="flex shrink-0 items-center justify-center gap-1 whitespace-nowrap rounded-full bg-white px-2.5 py-1 text-[10px] font-black leading-tight text-orange-700 ring-1 ring-orange-200 sm:px-3 sm:text-xs">
                            <span x-text="summary.pending || 0"></span>
                            <span>pending</span>
                        </span>
                    </div>
                </div>
                <div class="space-y-4 p-5">
                    <button type="button" x-show="primaryWallet() || openSession()" @click="openPrimaryAgentAction()" class="flex w-full items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-orange-600 to-orange-700 px-5 py-5 text-base font-black text-white shadow-xl shadow-orange-500/25 transition hover:from-orange-700 hover:to-orange-800">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                        </svg>
                        <span x-text="openSession() ? 'Scan Package' : 'Start Session'"></span>
                    </button>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Assigned wallet</p>
                        <p class="mt-2 text-lg font-black" :class="activeWallets().length ? 'text-slate-900' : 'text-amber-800'" x-text="assignedWalletHeading()"></p>
                        <p x-show="activeWallets().length === 1" class="mt-1 text-sm font-semibold text-slate-600">
                            <span x-text="activeWallets()[0]?.phone_number || '-'"></span>
                            <span x-show="activeWallets()[0]?.account_owner"> · </span>
                            <span x-text="activeWallets()[0]?.account_owner || ''"></span>
                        </p>
                        <div x-show="activeWallets().length > 1" class="mt-2 flex flex-wrap gap-2">
                            <template x-for="wallet in activeWallets()" :key="wallet.id">
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200" x-text="wallet.phone_number"></span>
                            </template>
                        </div>
                        <p class="mt-2 text-xs font-medium" :class="activeWallets().length ? 'text-slate-500' : 'text-amber-700'" x-text="assignedWalletHelpText()"></p>
                    </div>
                </div>
            </section>

            <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-300/30">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Today’s Session</p>
                <div class="mt-3">
                    <p class="text-lg font-black text-slate-900" x-text="openSession() ? (sessionStartedToday(openSession()) ? 'Session open' : 'Previous session open') : (todaySession() ? statusLabel(todaySession().status) : 'No session today')"></p>
                    <div class="mt-3 flex items-start gap-2 rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400" x-text="openSession() ? 'Started' : (todaySession()?.closed_at ? 'Closed' : (todaySession() ? 'Started' : 'Session status'))"></p>
                            <p class="mt-0.5 text-sm font-bold leading-5 text-slate-700" x-text="openSession() ? formatDateTime(openSession().started_at) : (todaySession() ? formatDateTime(todaySession().closed_at || todaySession().started_at) : 'Start a session before recording payments.')"></p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <p class="text-[10px] font-bold uppercase text-slate-400">Opening</p>
                        <p class="mt-1 text-sm font-black text-slate-900">GHS <span x-text="formatMoney(todaySession()?.opening_balance || 0)"></span></p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3">
                        <p class="text-[10px] font-bold uppercase text-emerald-600">Recorded</p>
                        <p class="mt-1 text-sm font-black text-emerald-800">GHS <span x-text="formatMoney(agentRecordedToday())"></span></p>
                    </div>
                </div>
                <div x-show="openSession()" class="mt-4 flex flex-col gap-2">
                    <button type="button" x-show="openSession()" @click="openCloseSession(openSession())" class="rounded-xl border-2 border-slate-200 bg-slate-900 px-4 py-3 text-sm font-black text-white shadow-sm shadow-slate-900/10 transition hover:border-slate-900 hover:bg-slate-800">Close Session</button>
                </div>
            </aside>
        </div>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-300/30">
            <div class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                <span class="min-w-0">
                    <span class="block text-base font-black text-slate-900">Payment Calls</span>
                    <span class="block text-sm text-slate-500"><span x-text="meta.total || 0"></span> recipients to call or follow up</span>
                </span>
                <span class="flex shrink-0 items-center gap-2">
                    <a :href="config.reportsUrl" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 shadow-sm transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 19h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Reports
                    </a>
                    <span class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full bg-orange-50 px-3 py-1 text-xs font-black text-orange-700 ring-1 ring-orange-100" x-text="`${summary.pending || 0} pending`"></span>
                </span>
            </div>
            <div class="border-t border-slate-100 p-4">
                <div class="mb-3 flex items-center justify-between gap-2 sm:gap-3">
                    <div class="min-w-0" style="flex: 0 1 360px; width: 360px; max-width: calc(100% - 8.5rem);">
                        <input type="text" x-model="search" @input="onSearch()" placeholder="Search"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-orange-400">
                    </div>
                    <button type="button" @click="loadData(1)" class="ml-auto inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 sm:px-4">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Refresh</span>
                    </button>
                </div>
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <div class="block divide-y divide-slate-100 md:hidden">
                        <div x-show="loading" class="px-4 py-10 text-center text-sm font-bold text-slate-400">Loading your queue...</div>
                        <div x-show="!loading && tasks.length === 0" class="px-4 py-10 text-center">
                            <p class="text-sm font-bold text-slate-500">No assigned packages yet. Scan a package to start.</p>
                        </div>
                        <template x-for="task in tasks" :key="task.id">
                            <article @click="openTask(task)" class="cursor-pointer p-4 transition hover:bg-orange-50/20">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-base font-black text-slate-900" x-text="task.recipient_name || 'No recipient'"></p>
                                        <p class="mt-0.5 truncate text-sm font-semibold text-slate-500" x-text="task.recipient_phone || 'No phone'"></p>
                                        <p class="truncate text-xs font-semibold text-slate-400" x-text="task.delivery_town || 'No location'"></p>
                                    </div>
                                    <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black" :class="feeStatusClass(task)" x-text="feeStatusLabel(task)"></span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Packages</p>
                                        <p class="mt-1 text-sm font-bold text-slate-800" x-text="`${task.package_count || 1} package${(task.package_count || 1) === 1 ? '' : 's'}`"></p>
                                        <p class="truncate font-mono text-[11px] text-slate-500" x-text="task.is_group ? (task.batch_number || 'No batch') : (task.tracking_code || '')"></p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Delivery Fee</p>
                                        <p class="mt-1 text-sm font-black text-slate-900" x-text="task.fee_amount !== null ? 'GHS ' + formatMoney(task.fee_amount) : 'No fee set'"></p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Call Result</p>
                                        <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="task.call_result_label || 'Not called'"></p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Wallet</p>
                                        <p class="mt-1 truncate text-sm font-bold text-slate-800" x-text="primaryWallet()?.name || '-'"></p>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center gap-2">
                                    <button type="button" x-show="receiptUrlFor(task)" @click.stop="openTaskReceipt(task)" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-3 text-xs font-black text-orange-700 transition hover:bg-orange-100">View Receipt</button>
                                    <button type="button" @click.stop="openTask(task)" class="inline-flex flex-1 items-center justify-center rounded-xl border-2 border-orange-600 bg-orange-600 px-4 py-3 text-xs font-black text-white shadow-lg shadow-orange-600/15 transition hover:border-orange-700 hover:bg-orange-700">Record Payment</button>
                                </div>
                            </article>
                        </template>
                    </div>
                    <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[1020px] divide-y divide-slate-100 text-xs">
                        <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Recipient</th>
                                <th class="px-4 py-3 text-left">Packages</th>
                                <th class="px-4 py-3 text-left">Delivery fee</th>
                                <th class="px-4 py-3 text-center">Payment status</th>
                                <th class="px-4 py-3 text-center">Call result</th>
                                <th class="px-4 py-3 text-left">Wallet</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-if="loading"><tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Loading your queue...</td></tr></template>
                            <template x-if="!loading && tasks.length === 0"><tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">No assigned packages yet. Scan a package to start.</td></tr></template>
                            <template x-for="task in tasks" :key="task.id">
                                <tr @click="openTask(task)" class="cursor-pointer hover:bg-orange-50/20">
                                    <td class="px-4 py-3">
                                        <p class="font-black text-slate-900" x-text="task.recipient_name || 'No recipient'"></p>
                                        <p class="text-slate-500" x-text="task.recipient_phone || 'No phone'"></p>
                                        <p class="text-slate-400" x-text="task.delivery_town || 'No location'"></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-slate-900" x-text="`${task.package_count || 1} package${(task.package_count || 1) === 1 ? '' : 's'}`"></p>
                                        <p class="font-mono text-slate-500" x-text="task.is_group ? (task.batch_number || 'No batch') : (task.tracking_code || '')"></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-black text-slate-900" x-text="task.fee_amount !== null ? 'GHS ' + formatMoney(task.fee_amount) : 'No fee set'"></p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold" :class="feeStatusClass(task)" x-text="feeStatusLabel(task)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <p class="font-bold text-slate-800" x-text="task.call_result_label || 'Not called'"></p>
                                        <p x-show="task.last_call_at" class="mt-1 whitespace-nowrap text-[11px] text-slate-400" x-text="formatDateTime(task.last_call_at)"></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-slate-700" x-text="primaryWallet()?.name || '-'"></p>
                                        <p class="text-slate-500" x-text="primaryWallet()?.phone_number || '-'"></p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" x-show="receiptUrlFor(task)" @click.stop="openTaskReceipt(task)" class="whitespace-nowrap rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-[11px] font-bold text-orange-700 transition hover:bg-orange-100">View Receipt</button>
                                            <button type="button" @click.stop="openTask(task)" class="whitespace-nowrap rounded-xl bg-slate-900 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-slate-800">Record Payment</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                    <div class="border-t border-slate-200/50 bg-slate-50/30 px-3 py-2.5 sm:px-4">
                        <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap sm:justify-between">
                            <p class="shrink-0 text-[11px] text-slate-600 sm:text-xs">
                                <span class="sm:hidden"><span x-text="meta.from"></span>-<span x-text="meta.to"></span> of <span x-text="meta.total"></span></span>
                                <span class="hidden sm:inline">Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span> results</span>
                            </p>
                            <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <span class="text-[11px] font-medium text-slate-600 sm:text-xs">Rows</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open" class="inline-flex min-w-[52px] items-center justify-between gap-1.5 rounded-lg border border-slate-200/70 bg-white/70 px-2 py-1 text-[11px] font-medium text-slate-700 backdrop-blur-sm transition-colors hover:bg-white/90 sm:min-w-[60px] sm:px-2.5 sm:text-xs">
                                            <span x-text="taskPerPage"></span>
                                            <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full right-0 z-[9999] mb-1 w-16 rounded-lg border border-slate-200/70 bg-white/95 p-1 shadow-lg backdrop-blur-xl" style="display:none">
                                            <template x-for="size in [10, 20, 25, 50, 100]" :key="size">
                                                <button type="button" @click="taskPerPage = size; loadData(1); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="taskPerPage === size ? 'bg-slate-100/70' : ''" x-text="size"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-[11px] font-medium text-slate-600 sm:text-xs">Page <span x-text="meta.current_page"></span>/<span x-text="meta.last_page"></span></span>
                                <div class="flex space-x-1">
                                    <button type="button" @click="goPage(1)" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="goPage(meta.current_page - 1)" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="goPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                    <button type="button" @click="goPage(meta.last_page)" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="!agentOnly" class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap gap-5 border-b border-slate-200">
            <button type="button" @click="setTab('all')" :class="tabLinkClass('all')" class="-mb-px border-b-2 px-1 pb-3 text-sm font-bold transition">All</button>
            <button type="button" @click="setTab('local_delivery')" :class="tabLinkClass('local_delivery')" class="-mb-px border-b-2 px-1 pb-3 text-sm font-bold transition">Local Delivery</button>
            <button type="button" @click="setTab('warehouse_transfer')" :class="tabLinkClass('warehouse_transfer')" class="-mb-px border-b-2 px-1 pb-3 text-sm font-bold transition">Warehouse Transfer</button>
            <button type="button" @click="setTab('mine')" :class="tabLinkClass('mine')" class="-mb-px border-b-2 px-1 pb-3 text-sm font-bold transition">Assigned to Me</button>
            <button type="button" @click="setTab('sessions')" :class="tabLinkClass('sessions')" class="-mb-px border-b-2 px-1 pb-3 text-sm font-bold transition">Sessions</button>
            <?php if($canManageWallets): ?>
            <button type="button" @click="setTab('wallets')" :class="tabLinkClass('wallets')" class="-mb-px border-b-2 px-1 pb-3 text-sm font-bold transition">Wallets</button>
            <?php endif; ?>
        </div>

    </div>

    <div x-show="!agentOnly && !['sessions', 'wallets'].includes(activeTab)" x-cloak class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
            <div class="border-b border-slate-200/60 px-5 py-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h10M7 12h10M7 17h6"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900" x-text="queueTitle()"></h2>
                            <p class="text-sm text-slate-500"><span x-text="meta.total || 0"></span> recipient payment tasks</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                                <template x-for="col in taskColumns" :key="col.key">
                                    <button type="button" @click="toggleTaskColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <span x-text="col.label"></span>
                                        <svg x-show="taskVisibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                                <button type="button" @click="exportTasks('excel'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</button>
                                <button type="button" @click="exportTasks('pdf'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</button>
                                <button type="button" @click="exportTasks('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                                <button type="button" @click="printTasks(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4">
                <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative w-full sm:w-80">
                            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="search" @input="onSearch()" placeholder="Search package, recipient, phone, town"
                                   class="w-full rounded-xl border border-slate-200/70 bg-white/80 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        </div>

                        <select x-model="taskStatusFilter" @change="loadData(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In progress</option>
                            <option value="paid">Paid</option>
                            <option value="waived">Waived</option>
                            <option value="failed">Failed</option>
                            <option value="disputed">Disputed</option>
                        </select>

                        <select x-show="activeTab !== 'mine'" x-model="taskAgentFilter" @change="loadData(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                            <option value="">All agents</option>
                            <template x-for="worker in workers" :key="worker.id">
                                <option :value="worker.id" x-text="worker.name"></option>
                            </template>
                        </select>
                    </div>

                    <button type="button" @click="openScanModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                        </svg>
                        Scan Package
                    </button>
                </div>

            <?php if($canAssign): ?>
            <div x-show="activeTab !== 'mine'" class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200/70 bg-slate-50/70 px-4 py-3 md:flex-row md:items-center md:justify-between">
                <p class="text-xs font-semibold text-slate-500"><span x-text="selectedTaskIds.length"></span> selected</p>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <select x-model="bulkAssignUserId" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-orange-400">
                        <option value="">Assign selected to...</option>
                        <template x-for="worker in workers" :key="worker.id">
                            <option :value="worker.id" x-text="worker.name"></option>
                        </template>
                    </select>
                    <button type="button" @click="bulkAssign()" :disabled="!selectedTaskIds.length || !bulkAssignUserId || loading"
                            class="rounded-xl bg-orange-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40">Assign</button>
                </div>
            </div>
            <?php endif; ?>

            <div class="relative rounded-xl border border-slate-200/70">
                <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
                <div class="overflow-x-auto">
                <table class="w-full min-w-[1180px] divide-y divide-slate-200/60 text-xs">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <?php if($canAssign): ?><th x-show="activeTab !== 'mine'" class="w-10 px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500"></th><?php endif; ?>
                            <template x-for="col in taskColumns" :key="col.key">
                                <th x-show="taskVisibleColumns[col.key]" @click="taskSort(col.key)" class="px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500" :class="col.sortable ? 'cursor-pointer' : ''">
                                    <div class="flex items-center gap-1">
                                        <span x-text="col.label"></span>
                                        <svg x-show="col.sortable" class="h-2.5 w-2.5" :class="taskSortBy === col.key ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                        </svg>
                                    </div>
                                </th>
                            </template>
                            <th class="px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="loading">
                            <tr><td colspan="10" class="px-4 py-14 text-center text-slate-400">Loading recipient payment queue...</td></tr>
                        </template>
                        <template x-if="!loading && tasks.length === 0">
                            <tr><td colspan="10" class="px-4 py-14 text-center text-slate-400">No recipient payment tasks found.</td></tr>
                        </template>
                        <template x-for="task in tasks" :key="task.id">
                            <tr class="hover:bg-orange-50/20">
                                <?php if($canAssign): ?>
                                <td x-show="activeTab !== 'mine'" class="px-4 py-2.5 align-middle">
                                    <input type="checkbox" :value="task.id" x-model="selectedTaskIds" class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                </td>
                                <?php endif; ?>
                                <td x-show="taskVisibleColumns.group" class="whitespace-nowrap px-4 py-2.5 align-middle">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold" :class="task.payment_group === 'local_delivery' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-orange-50 text-orange-700 ring-1 ring-orange-200'" x-text="groupLabel(task.payment_group)"></span>
                                </td>
                                <td x-show="taskVisibleColumns.batch" class="whitespace-nowrap px-3 py-2.5 align-middle">
                                    <p class="font-bold text-slate-800" x-text="task.is_group ? `${task.package_count || 0} package${(task.package_count || 0) === 1 ? '' : 's'}` : (task.batch_number || 'No batch')"></p>
                                    <p class="text-[11px] text-slate-400" x-text="task.is_group ? (task.batch_number || 'No batch') : (task.shipment_number || '')"></p>
                                </td>
                                <td x-show="taskVisibleColumns.package" class="px-3 py-2.5 align-middle">
                                    <p class="font-bold text-slate-900" x-text="task.is_group ? task.description : (task.description || 'Package')"></p>
                                    <p class="font-mono text-[11px] text-slate-500" x-text="task.is_group ? `${task.pending_count || 0} pending · ${task.paid_count || 0} paid` : (task.tracking_code || 'No tracking code')"></p>
                                </td>
                                <td x-show="taskVisibleColumns.recipient" class="px-3 py-2.5 align-middle">
                                    <p class="font-semibold text-slate-800" x-text="task.recipient_name || 'No recipient'"></p>
                                    <p class="text-[11px] text-slate-500" x-text="task.recipient_phone || 'No phone'"></p>
                                    <p class="text-[11px] text-slate-400" x-text="task.delivery_town || 'No town'"></p>
                                </td>
                                <td x-show="taskVisibleColumns.method" class="whitespace-nowrap px-3 py-2.5 align-middle">
                                    <span class="text-slate-600" x-text="task.is_group && !task.delivery_method ? 'Mixed methods' : methodLabel(task.delivery_method)"></span>
                                </td>
                                <td x-show="taskVisibleColumns.fee" class="whitespace-nowrap px-3 py-2.5 align-middle">
                                    <p class="font-bold text-slate-900" x-text="task.fee_amount !== null ? 'GHS ' + formatMoney(task.fee_amount) : 'No fee set'"></p>
                                    <p class="text-[10px] text-slate-400" x-text="task.fee_status || ''"></p>
                                </td>
                                <td x-show="taskVisibleColumns.status" class="whitespace-nowrap px-3 py-2.5 align-middle">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold" :class="statusClass(task.status)" x-text="statusLabel(task.status)"></span>
                                </td>
                                <td x-show="taskVisibleColumns.assigned" class="whitespace-nowrap px-3 py-2.5 align-middle">
                                    <p class="text-slate-600" x-text="task.assigned_to || 'Unassigned'"></p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-center align-middle">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" x-show="receiptUrlFor(task)" @click.stop="openTaskReceipt(task)" class="whitespace-nowrap rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-[11px] font-bold text-orange-700 transition hover:bg-orange-100">View Receipt</button>
                                        <button type="button" @click="openTask(task)" class="rounded-xl bg-slate-900 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-slate-800" x-text="task.is_group ? 'Process Group' : 'Process'"></button>
                                        <button type="button" x-show="task.can_release" @click="releaseTask(task)" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] font-bold text-rose-700 transition hover:bg-rose-100">Release</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200/50 bg-slate-50/30 px-4 py-2.5">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-xs text-slate-600">Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span> results</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-600">Rows per page</span>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" @click="open = !open" class="inline-flex min-w-[60px] items-center justify-between gap-1.5 rounded-lg border border-slate-200/70 bg-white/70 px-2.5 py-1 text-xs font-medium text-slate-700 backdrop-blur-sm transition-colors hover:bg-white/90">
                                    <span x-text="taskPerPage"></span>
                                    <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full right-0 z-[9999] mb-1 w-16 rounded-lg border border-slate-200/70 bg-white/95 p-1 shadow-lg backdrop-blur-xl" style="display:none">
                                    <template x-for="size in [10, 20, 25, 50, 100]" :key="size">
                                        <button type="button" @click="taskPerPage = size; loadData(1); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="taskPerPage === size ? 'bg-slate-100/70' : ''" x-text="size"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <span class="text-xs font-medium text-slate-600">Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span></span>
                        <div class="flex space-x-1">
                            <button type="button" @click="goPage(1)" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" @click="goPage(meta.current_page - 1)" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" @click="goPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button type="button" @click="goPage(meta.last_page)" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </div>
        </div>

    <div x-show="!agentOnly && activeTab === 'sessions'" x-cloak>
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
            <div class="border-b border-slate-200/60 px-5 py-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v8m4-4H8m10 8H6a2 2 0 01-2-2V6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Payment Sessions</h2>
                            <p class="text-sm text-slate-500"><span x-text="sessionMeta.total || 0"></span> wallet balance sessions</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                                <template x-for="col in sessionColumns" :key="col.key">
                                    <button type="button" @click="toggleSessionColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <span x-text="col.label"></span>
                                        <svg x-show="sessionVisibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                                <button type="button" @click="exportSessions('excel'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</button>
                                <button type="button" @click="exportSessions('pdf'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</button>
                                <button type="button" @click="exportSessions('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                                <button type="button" @click="printSessions(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                            </div>
                        </div>

                        <button type="button" @click="openStartSession()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-orange-600/20 transition hover:bg-orange-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/>
                            </svg>
                            Start Session
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4">
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <div class="relative w-full sm:w-72">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="sessionSearch" @input.debounce.500ms="loadSessions(1)" placeholder="Search agent, wallet, phone, warehouse"
                               class="w-full rounded-xl border border-slate-200/70 bg-white/80 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>

                    <select x-model="sessionStatusFilter" @change="loadSessions(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                        <option value="">All statuses</option>
                        <option value="open">Open</option>
                        <option value="submitted">Submitted</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="disputed">Disputed</option>
                        <option value="approved">Approved</option>
                    </select>

                    <select x-model="sessionWalletFilter" @change="loadSessions(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                        <option value="">All wallets</option>
                        <template x-for="wallet in wallets" :key="wallet.id">
                            <option :value="wallet.id" x-text="walletLabel(wallet)"></option>
                        </template>
                    </select>

                    <select x-model="sessionAgentFilter" @change="loadSessions(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                        <option value="">All agents</option>
                        <template x-for="worker in workers" :key="worker.id">
                            <option :value="worker.id" x-text="worker.name"></option>
                        </template>
                    </select>

                    <select x-show="warehouses.length > 1" x-model="sessionWarehouseFilter" @change="loadSessions(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                        <option value="">All warehouses</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>

                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            x-ref="sessionDateRange"
                            placeholder="Session date range"
                            class="w-full cursor-pointer rounded-xl border border-slate-200/70 bg-white/80 py-2 pl-10 pr-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                            readonly
                        >
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <div class="relative rounded-xl border border-slate-200/70">
                    <div x-show="sessionLoading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1180px] divide-y divide-slate-200/60 text-xs">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <template x-for="col in sessionColumns" :key="col.key">
                                        <th x-show="sessionVisibleColumns[col.key]" @click="sessionSort(col.key)" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500" :class="col.sortable ? 'cursor-pointer' : ''">
                                            <div class="flex items-center gap-1">
                                                <span x-text="col.label"></span>
                                                <svg x-show="col.sortable" class="h-2.5 w-2.5" :class="sessionSortBy === col.key ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                                </svg>
                                            </div>
                                        </th>
                                    </template>
                                    <th class="px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-if="!sessionLoading && sessions.length === 0">
                                    <tr>
                                        <td colspan="13" class="px-4 py-14 text-center text-slate-400">No payment sessions match the current filters.</td>
                                    </tr>
                                </template>
                                <template x-for="session in sessions" :key="session.id">
                                    <tr class="transition hover:bg-orange-50/20">
                                        <td x-show="sessionVisibleColumns.agent" class="whitespace-nowrap px-4 py-2.5 font-semibold text-slate-900" x-text="session.agent || '-'"></td>
                                        <td x-show="sessionVisibleColumns.wallet" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="session.wallet || '-'"></td>
                                        <td x-show="sessionVisibleColumns.provider" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="session.wallet_provider || '-'"></td>
                                        <td x-show="sessionVisibleColumns.phone" class="whitespace-nowrap px-4 py-2.5 font-medium text-slate-800" x-text="session.wallet_phone || '-'"></td>
                                        <td x-show="sessionVisibleColumns.warehouse" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="session.warehouse || warehouseName(session.warehouse_id)"></td>
                                        <td x-show="sessionVisibleColumns.opening_balance" class="whitespace-nowrap px-4 py-2.5 text-slate-600">GHS <span x-text="formatMoney(session.opening_balance)"></span></td>
                                        <td x-show="sessionVisibleColumns.closing_balance" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="session.closing_balance === null ? '-' : 'GHS ' + formatMoney(session.closing_balance)"></td>
                                        <td x-show="sessionVisibleColumns.expected_closing_balance" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="session.expected_closing_balance === null ? '-' : 'GHS ' + formatMoney(session.expected_closing_balance)"></td>
                                        <td x-show="sessionVisibleColumns.variance" class="whitespace-nowrap px-4 py-2.5 font-bold" :class="Number(session.variance || 0) === 0 ? 'text-slate-500' : 'text-rose-600'" x-text="session.variance === null ? '-' : 'GHS ' + formatMoney(session.variance)"></td>
                                        <td x-show="sessionVisibleColumns.started_at" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="formatDateTime(session.started_at)"></td>
                                        <td x-show="sessionVisibleColumns.closed_at" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="formatDateTime(session.closed_at)"></td>
                                        <td x-show="sessionVisibleColumns.status" class="whitespace-nowrap px-4 py-2.5">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold" :class="statusClass(session.status)" x-text="statusLabel(session.status)"></span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-center">
                                            <div class="inline-flex items-center gap-1">
                                                <button type="button" x-show="session.status === 'open'" @click="openCloseSession(session)" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100" title="Close session">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Close Session
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-200/50 bg-slate-50/30 px-4 py-2.5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="text-xs text-slate-600">
                                Showing
                                <span x-text="sessionMeta.from"></span>
                                to
                                <span x-text="sessionMeta.to"></span>
                                of
                                <span x-text="sessionMeta.total"></span>
                                results
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open" class="inline-flex min-w-[60px] items-center justify-between gap-1.5 rounded-lg border border-slate-200/70 bg-white/70 px-2.5 py-1 text-xs font-medium text-slate-700 backdrop-blur-sm transition-colors hover:bg-white/90">
                                            <span x-text="sessionPerPage"></span>
                                            <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full right-0 z-[9999] mb-1 w-16 rounded-lg border border-slate-200/70 bg-white/95 p-1 shadow-lg backdrop-blur-xl" style="display:none">
                                            <template x-for="size in [10, 25, 50, 100]" :key="size">
                                                <button type="button" @click="sessionPerPage = size; loadSessions(1); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="sessionPerPage === size ? 'bg-slate-100/70' : ''" x-text="size"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs font-medium text-slate-600">
                                    Page
                                    <span x-text="sessionMeta.current_page"></span>
                                    of
                                    <span x-text="sessionMeta.last_page"></span>
                                </div>

                                <div class="flex space-x-1">
                                    <button type="button" @click="sessionFirstPage()" :disabled="sessionMeta.current_page === 1" :class="sessionMeta.current_page === 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button type="button" @click="sessionPreviousPage()" :disabled="sessionMeta.current_page === 1" :class="sessionMeta.current_page === 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </button>
                                    <button type="button" @click="sessionNextPage()" :disabled="sessionMeta.current_page === sessionMeta.last_page" :class="sessionMeta.current_page === sessionMeta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    <button type="button" @click="sessionLastPage()" :disabled="sessionMeta.current_page === sessionMeta.last_page" :class="sessionMeta.current_page === sessionMeta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if($canManageWallets): ?>
    <div x-show="!agentOnly && activeTab === 'wallets'" x-cloak>
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
            <div class="border-b border-slate-200/60 px-5 py-4">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm5 4h4"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Payment Wallets</h2>
                            <p class="text-sm text-slate-500"><span x-text="walletMeta.total || 0"></span> approved wallet records</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                                <template x-for="col in walletColumns" :key="col.key">
                                    <button type="button" @click="toggleWalletColumn(col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <span x-text="col.label"></span>
                                        <svg x-show="walletVisibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-white">
                                <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                                <button type="button" @click="exportWallets('excel'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</button>
                                <button type="button" @click="exportWallets('pdf'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</button>
                                <button type="button" @click="exportWallets('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                                <button type="button" @click="printWallets(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                            </div>
                        </div>

                        <button type="button" @click="openWalletModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-orange-600/20 transition hover:bg-orange-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/>
                            </svg>
                            Add Wallet
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4">
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <div class="relative w-full sm:w-72">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="walletSearch" @input.debounce.500ms="loadWallets(1)" placeholder="Search wallets, phone, owner, agent"
                               class="w-full rounded-xl border border-slate-200/70 bg-white/80 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>

                    <select x-model="walletStatusFilter" @change="loadWallets(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <select x-model="walletProviderFilter" @change="loadWallets(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                        <option value="">All providers</option>
                        <template x-for="provider in walletProviderOptions" :key="provider">
                            <option :value="provider" x-text="provider"></option>
                        </template>
                    </select>

                    <select x-show="warehouses.length > 1" x-model="walletWarehouseFilter" @change="loadWallets(1)" class="rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400">
                        <option value="">All warehouses</option>
                        <option value="global">Global wallets</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>

                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            x-ref="walletPaymentRange"
                            placeholder="Payment date range"
                            class="w-full cursor-pointer rounded-xl border border-slate-200/70 bg-white/80 py-2 pl-10 pr-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                            readonly
                        >
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                    <div class="relative rounded-xl border border-slate-200/70">
                        <div x-show="walletLoading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[1180px] divide-y divide-slate-200/60 text-xs">
                                <thead class="bg-slate-50/80">
                                    <tr>
                                        <template x-for="col in walletColumns" :key="col.key">
                                            <th x-show="walletVisibleColumns[col.key]" @click="walletSort(col.key)" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500" :class="col.sortable ? 'cursor-pointer' : ''">
                                                <div class="flex items-center gap-1">
                                                    <span x-text="col.label"></span>
                                                    <svg x-show="col.sortable" class="h-2.5 w-2.5" :class="walletSortBy === col.key ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                                    </svg>
                                                </div>
                                            </th>
                                        </template>
                                        <th class="px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-if="!walletLoading && walletRows.length === 0">
                                        <tr>
                                            <td colspan="12" class="px-4 py-14 text-center text-slate-400">No payment wallets match the current filters.</td>
                                        </tr>
                                    </template>
                                    <template x-for="wallet in walletRows" :key="wallet.id">
                                        <tr class="transition hover:bg-orange-50/20">
                                            <td x-show="walletVisibleColumns.name" class="whitespace-nowrap px-4 py-2.5 font-semibold text-slate-900" x-text="wallet.name"></td>
                                            <td x-show="walletVisibleColumns.provider" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="wallet.provider"></td>
                                            <td x-show="walletVisibleColumns.phone_number" class="whitespace-nowrap px-4 py-2.5 font-medium text-slate-800" x-text="wallet.phone_number"></td>
                                            <td x-show="walletVisibleColumns.account_owner" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="wallet.account_owner || '-'"></td>
                                            <td x-show="walletVisibleColumns.warehouse" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="wallet.warehouse_name || warehouseName(wallet.warehouse_id)"></td>
                                            <td x-show="walletVisibleColumns.assigned_agents" class="px-4 py-2.5 text-slate-600" x-text="assignedAgentNames(wallet)"></td>
                                            <td x-show="walletVisibleColumns.recorded_amount" class="whitespace-nowrap px-4 py-2.5 font-bold text-slate-900">GHS <span x-text="formatMoney(wallet.recorded_amount)"></span></td>
                                            <td x-show="walletVisibleColumns.payment_count" class="whitespace-nowrap px-4 py-2.5 text-center text-slate-600" x-text="wallet.payment_count || 0"></td>
                                            <td x-show="walletVisibleColumns.last_payment_at" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="formatDateTime(wallet.last_payment_at)"></td>
                                            <td x-show="walletVisibleColumns.history" class="whitespace-nowrap px-4 py-2.5 text-slate-500" x-text="wallet.history_label || 'Unused'"></td>
                                            <td x-show="walletVisibleColumns.status" class="whitespace-nowrap px-4 py-2.5">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold" :class="wallet.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200'" x-text="wallet.is_active ? 'Active' : 'Inactive'"></span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2.5 text-center">
                                                <div class="inline-flex items-center gap-1">
                                                    <button type="button" @click="openWalletModal(wallet)" class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600" title="Edit wallet">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                        </svg>
                                                    </button>
                                                    <button type="button" x-show="wallet.is_active" @click="setWalletStatus(wallet, false)" class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-amber-50 hover:text-amber-600" title="Deactivate wallet">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                        </svg>
                                                    </button>
                                                    <button type="button" x-show="!wallet.is_active" @click="setWalletStatus(wallet, true)" class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-teal-50 hover:text-teal-600" title="Activate wallet">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </button>
                                                    <button type="button" x-show="wallet.can_delete" @click="confirmWalletDelete(wallet)" class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600" title="Delete wallet">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t border-slate-200/50 bg-slate-50/30 px-4 py-2.5">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="text-xs text-slate-600">
                                    Showing
                                    <span x-text="walletMeta.from"></span>
                                    to
                                    <span x-text="walletMeta.to"></span>
                                    of
                                    <span x-text="walletMeta.total"></span>
                                    results
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                        <div x-data="{ open: false }" class="relative">
                                            <button type="button" @click="open = !open" class="inline-flex min-w-[60px] items-center justify-between gap-1.5 rounded-lg border border-slate-200/70 bg-white/70 px-2.5 py-1 text-xs font-medium text-slate-700 backdrop-blur-sm transition-colors hover:bg-white/90">
                                                <span x-text="walletPerPage"></span>
                                                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full right-0 z-[9999] mb-1 w-16 rounded-lg border border-slate-200/70 bg-white/95 p-1 shadow-lg backdrop-blur-xl" style="display:none">
                                                <template x-for="size in [10, 25, 50, 100]" :key="size">
                                                    <button type="button" @click="walletPerPage = size; loadWallets(1); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="walletPerPage === size ? 'bg-slate-100/70' : ''" x-text="size"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-xs font-medium text-slate-600">
                                        Page
                                        <span x-text="walletMeta.current_page"></span>
                                        of
                                        <span x-text="walletMeta.last_page"></span>
                                    </div>

                                    <div class="flex space-x-1">
                                        <button type="button" @click="walletFirstPage()" :disabled="walletMeta.current_page === 1" :class="walletMeta.current_page === 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                                            </svg>
                                        </button>
                                        <button type="button" @click="walletPreviousPage()" :disabled="walletMeta.current_page === 1" :class="walletMeta.current_page === 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                            </svg>
                                        </button>
                                        <button type="button" @click="walletNextPage()" :disabled="walletMeta.current_page === walletMeta.last_page" :class="walletMeta.current_page === walletMeta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                        <button type="button" @click="walletLastPage()" :disabled="walletMeta.current_page === walletMeta.last_page" :class="walletMeta.current_page === walletMeta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white/80'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200/70 bg-white/50 text-slate-600 transition-colors">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <template x-teleport="body">
    <div x-show="scanModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
        <div @click.stop class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900">Scan Package</h3>
                        <p class="mt-1 text-sm text-slate-500">Scan the printed label to load the recipient payment task.</p>
                    </div>
                </div>
                <button type="button" @click="closeScanModal()" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4 p-5">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                    <video x-ref="scanVideo" class="hidden aspect-video w-full object-contain" playsinline muted></video>
                    <canvas x-ref="scanCanvas" class="hidden"></canvas>
                    <div x-show="scannerActive" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-between p-4" style="display:none">
                        <div class="rounded-full bg-black/55 px-3 py-1.5 text-xs font-bold text-white shadow-lg" x-text="scannerStatus || 'Scanning barcode...'"></div>
                        <div></div>
                        <p class="rounded-full bg-black/55 px-3 py-1.5 text-[11px] font-semibold text-white">Point the camera anywhere on the package label</p>
                    </div>
                    <div x-show="!scannerActive" class="flex aspect-video flex-col items-center justify-center gap-3 p-6 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-white">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2M3 17v2a2 2 0 002 2h2m10 0h2a2 2 0 002-2v-2M7 12h10"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-white" x-text="scannerStatus || 'Camera scanner is ready when supported by this browser.'"></p>
                        <button type="button" @click="startScanner()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700">Start Camera Scan</button>
                    </div>
                </div>

                <div x-show="scanModalMessage" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700" x-text="scanModalMessage"></div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Manual entry</label>
                    <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="scanPackage()">
                        <input type="text" x-model="scanCode" @input="scanModalMessage = ''" x-ref="scanCodeInput" placeholder="Enter or paste label code"
                               class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <button type="submit" x-show="scanCode.trim()" class="rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-base font-black text-white shadow-lg shadow-slate-900/20 transition hover:border-slate-800 hover:bg-slate-800 sm:text-sm">Load Package</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </template>

    <template x-teleport="body">
    <div x-show="taskModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] overflow-y-auto" style="display:none">
        <div class="fixed inset-0 bg-slate-900/55 backdrop-blur-sm" @click="taskModalOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-3 sm:p-4">
            <div @click.stop class="relative flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200/60 bg-white/95 shadow-2xl backdrop-blur-xl">
                <div class="relative shrink-0 rounded-t-2xl border-b border-slate-200/60 bg-gradient-to-r from-slate-50 to-orange-50/40 px-3 py-3 sm:px-6 sm:py-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2.5 sm:gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25 sm:h-12 sm:w-12">
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
                        <button type="button" @click="taskModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 bg-white/90 p-2 text-slate-400 shadow-sm transition hover:border-slate-300 hover:text-slate-700">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div class="max-h-[calc(100vh-220px)] min-h-0 flex-1 space-y-5 overflow-y-auto bg-slate-50/70 px-4 py-5 sm:px-6 sm:py-6">
                    <section class="overflow-hidden rounded-2xl border border-orange-100 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-orange-100 bg-gradient-to-r from-orange-50 to-white px-4 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-black text-slate-900">Recipient Details</h4>
                                    <p class="text-xs font-medium text-slate-500">Confirm the contact before payment.</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button type="button" x-show="!recipientEditing" @click="recipientEditing = true" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 shadow-sm transition hover:border-orange-200 hover:text-orange-700">Edit</button>
                                <button type="button" x-show="recipientEditing" @click="saveRecipientContactDetails()" class="inline-flex items-center rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-black text-orange-700 shadow-sm transition hover:bg-orange-100">Save</button>
                            </div>
                        </div>
                        <div class="grid gap-4 p-4 md:grid-cols-2">
                            <div x-show="modalTasks().length === 1" class="md:col-span-2">
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Recipient name</label>
                                <div class="w-full rounded-xl border border-slate-100 bg-slate-50 px-3 py-3 text-base font-black text-slate-900 sm:py-2.5 sm:text-sm"
                                     x-text="singleModalTask()?.original_recipient_name || singleModalTask()?.recipient_name || 'No recipient'"></div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Phone number</label>
                                <div x-show="!recipientEditing" class="flex w-full items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 p-1.5">
                                    <span class="min-w-0 flex-1 truncate px-2 text-base font-black text-slate-900 sm:text-sm" x-text="taskForm.recipient_phone || '-'"></span>
                                    <a :href="recipientCallHref()" class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border border-emerald-100 bg-emerald-50 px-2.5 text-xs font-black text-emerald-700 transition hover:bg-emerald-100" title="Call recipient">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293a1.125 1.125 0 01-1.21.38 12.035 12.035 0 01-7.143-7.143 1.125 1.125 0 01.38-1.21l1.293-.97a1.125 1.125 0 00.417-1.173L6.963 3.102A1.125 1.125 0 005.872 2.25H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                                        <span>Call</span>
                                    </a>
                                </div>
                                <input x-show="recipientEditing" x-model="taskForm.recipient_phone" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:py-2.5 sm:text-sm">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Delivery location</label>
                                <div x-show="!recipientEditing" class="w-full rounded-xl border border-slate-100 bg-slate-50 px-3 py-3 text-base font-black text-slate-900 sm:py-2.5 sm:text-sm" x-text="taskForm.delivery_town || '-'"></div>
                                <div x-show="recipientEditing" class="relative" @click.away="closeTaskLocationResults()" @focusout="closeTaskLocationResultsSoon()">
                                    <input x-model="taskForm.delivery_town" @input="searchTaskLocation()" placeholder="Type town or location"
                                           class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:py-2.5 sm:text-sm">
                                    <div x-show="taskLocationResults.length" x-transition class="absolute left-0 right-0 z-30 mt-2 max-h-56 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-xl" style="display:none">
                                        <template x-for="loc in taskLocationResults" :key="loc.id">
                                            <button type="button" @click="selectTaskLocation(loc)" class="w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-orange-50">
                                                <span class="font-bold" x-text="loc.name"></span>
                                                <span class="block text-xs text-slate-500" x-text="loc.display"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border bg-white shadow-sm" :class="modalTasks().length === 1 ? 'border-orange-100' : 'border-slate-200'">
                        <div class="flex items-start justify-between gap-3 border-b px-4 py-3"
                             :class="modalTasks().length === 1 ? 'border-orange-100 bg-gradient-to-r from-orange-50 to-white' : 'border-slate-200 bg-gradient-to-r from-slate-50 to-white'">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-sm" :class="modalTasks().length === 1 ? 'bg-orange-600 shadow-orange-500/20' : 'bg-slate-900'">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8.5l5-3 5 3-5 3-5-3z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 13l5 3 5-3M10 16l5 3 5-3M4 13l5-3 5 3-5 3-5-3z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-black text-slate-900" x-text="modalTasks().length === 1 ? 'Package Details' : 'Packages'"></h4>
                                    <p class="text-xs font-medium text-slate-500" x-text="modalTasks().length === 1 ? 'Review this package before payment.' : 'Review package details before payment.'"></p>
                                </div>
                            </div>
                            <span x-show="modalTasks().length !== 1" class="shrink-0 rounded-full bg-orange-50 px-3 py-1.5 text-xs font-black text-orange-700 ring-1 ring-orange-100" x-text="`${modalTasks().length} packages`"></span>
                        </div>
                        <template x-if="modalTasks().length === 1">
                            <template x-for="child in modalTasks()" :key="child.id">
                                <div class="space-y-4 bg-gradient-to-br from-white via-white to-orange-50/40 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-lg font-black text-slate-900 sm:text-xl" x-text="child.description || 'Package'"></p>
                                            <p class="mt-1 font-mono text-xs font-bold text-orange-600" x-text="child.tracking_code || child.shipment_number || 'No tracking'"></p>
                                        </div>
                                        <span class="shrink-0 rounded-xl border border-orange-100 bg-white px-3 py-2 text-center shadow-sm">
                                            <span class="block text-[9px] font-black uppercase tracking-wide text-slate-400">Qty</span>
                                            <span class="block text-base font-black leading-none text-slate-900" x-text="child.quantity || 1"></span>
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-3">
                                        <div>
                                            <p class="text-xs font-black text-slate-900" x-text="photoCountLabel(child)"></p>
                                            <p class="text-xs font-medium text-slate-500" x-text="photoSourceLabel(child)"></p>
                                        </div>
                                        <button type="button" @click="openVendorPhotos(child)" :disabled="!packagePhotos(child).length"
                                                class="inline-flex items-center rounded-xl border px-4 py-2 text-xs font-black transition"
                                                :class="packagePhotos(child).length ? 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100' : 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'">
                                            View Photos
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </template>
                        <div x-show="modalTasks().length !== 1" class="overflow-x-auto">
                            <table class="min-w-[760px] w-full text-left">
                                <thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3">Package</th>
                                        <th class="px-4 py-3">Recipient</th>
                                        <th class="px-4 py-3 text-center">Qty</th>
                                        <th class="px-4 py-3">Location</th>
                                        <th class="px-4 py-3">Photos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="child in modalTasks()" :key="child.id">
                                        <tr class="bg-white align-top transition hover:bg-orange-50/30">
                                            <td class="px-4 py-3">
                                                <p class="max-w-[220px] truncate text-sm font-black text-slate-900" x-text="child.description || 'Package'"></p>
                                                <p class="mt-1 font-mono text-xs font-bold text-orange-600" x-text="child.tracking_code || child.shipment_number || 'No tracking'"></p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="max-w-[180px] truncate text-sm font-bold text-slate-800" x-text="child.original_recipient_name || child.recipient_name || 'No recipient'"></p>
                                                <p class="mt-1 text-xs font-medium text-slate-500" x-text="child.original_recipient_phone || child.recipient_phone || taskForm.recipient_phone || '-'"></p>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-sm font-black text-slate-800" x-text="child.quantity || 1"></span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="max-w-[220px] text-sm font-semibold leading-5 text-slate-700" x-text="child.original_delivery_town || child.delivery_town || 'No location'"></p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-slate-500" x-text="photoCountLabel(child)"></span>
                                                    <button type="button" @click="openVendorPhotos(child)" :disabled="!packagePhotos(child).length"
                                                            class="inline-flex items-center rounded-lg border px-2.5 py-1.5 text-xs font-black transition"
                                                            :class="packagePhotos(child).length ? 'border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100' : 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'">
                                                        View
                                                    </button>
                                                </div>
                                                <p class="mt-1 text-[10px] font-semibold text-slate-400" x-text="photoSourceLabel(child)"></p>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
                        <div class="flex items-center gap-3 border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-white px-3 py-3 sm:px-4">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 sm:h-10 sm:w-10">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-slate-900">Payment</h4>
                                <p class="text-xs font-medium text-slate-500">Record the negotiated fee for this recipient group.</p>
                            </div>
                        </div>
                        <div class="space-y-4 p-3 sm:p-4">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Delivery fee <span class="text-rose-500">*</span></label>
                                <div class="flex items-center rounded-xl border-2 border-slate-200 bg-white px-3 transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                                    <span class="mr-2 shrink-0 text-sm font-black text-slate-500">GHS</span>
                                    <input type="number" min="0" step="0.01" x-model="taskForm.amount" placeholder="0.00" class="min-w-0 w-full flex-1 border-0 bg-transparent py-3 text-xl font-black text-slate-900 outline-none">
                                </div>
                            </div>
                            <div x-show="activeWallets().length !== 1">
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Payment wallet <span class="text-rose-500">*</span></label>
                                <select x-model="taskForm.payment_wallet_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                                    <option value="">Select wallet</option>
                                    <template x-for="wallet in activeWallets()" :key="wallet.id">
                                        <option :value="wallet.id" x-text="walletLabel(wallet)"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">MoMo reference <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                                    <input x-model="taskForm.payment_reference" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm" placeholder="Payment reference">
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Call result <span class="text-rose-500">*</span></label>
                                    <select x-model="taskForm.outcome" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                                        <option value="answered">Answered</option>
                                        <option value="no_answer">No answer</option>
                                        <option value="callback">Call back later</option>
                                        <option value="wrong_number">Wrong number</option>
                                        <option value="payment_promised">Payment promised</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Receipt screenshot <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                                    <label class="flex min-w-0 cursor-pointer flex-col gap-3 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/70 px-3 py-3 transition hover:border-orange-300 hover:bg-orange-50/40 sm:flex-row sm:items-center sm:justify-between">
                                        <span class="min-w-0 max-w-full">
                                            <span class="block truncate text-sm font-bold text-slate-700" x-text="taskForm.payment_receipt_name || activePaymentReceiptName() || 'Upload MoMo receipt screenshot'"></span>
                                            <span class="block text-xs font-medium text-slate-400">PNG, JPG or WEBP up to 5MB</span>
                                        </span>
                                        <span class="inline-flex w-fit shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-black text-orange-700 shadow-sm ring-1 ring-orange-100">Choose</span>
                                        <input x-ref="paymentReceiptInput" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden" @change="handlePaymentReceiptChange($event)">
                                    </label>
                                    <div class="mt-2 flex justify-end">
                                        <button type="button" x-show="activePaymentReceiptUrl()" @click="openPaymentReceipt()" class="inline-flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-black text-orange-700 transition hover:bg-orange-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            View receipt
                                        </button>
                                    </div>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Notes</label>
                                    <textarea x-model="taskForm.notes" rows="2" placeholder="Call notes" class="w-full rounded-xl border-2 border-slate-200 px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="flex shrink-0 items-center justify-between gap-2 rounded-b-2xl border-t border-slate-200/60 bg-gradient-to-r from-slate-50/80 to-slate-100/50 px-3 py-3 sm:px-6 sm:py-5">
                    <button type="button" x-show="modalCanRelease()" @click="releaseActiveTask()" class="shrink-0 rounded-lg border border-rose-100 bg-white px-2.5 py-2 text-xs font-bold text-rose-500 transition hover:bg-rose-50 hover:text-rose-700 sm:rounded-xl sm:px-4 sm:py-3 sm:text-sm">Release</button>
                    <span x-show="!modalCanRelease()" class="shrink-0"></span>
                    <div class="ml-auto flex min-w-0 items-center justify-end gap-2">
                        <button type="button" @click="saveRecipientDetails()" class="shrink rounded-lg border-2 border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:rounded-xl sm:px-5 sm:py-3 sm:text-sm">Save Details</button>
                        <button type="button" @click="markPaid()" class="shrink-0 rounded-lg border-2 border-orange-600 bg-orange-600 px-3 py-2 text-xs font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 sm:rounded-xl sm:px-5 sm:py-3 sm:text-sm" x-text="paymentSubmitLabel()"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>

    <template x-teleport="body">
    <div x-show="vendorPhotoModalOpen" x-cloak x-transition.opacity @click="closePhotoLightbox()" @keydown.window.escape="closePhotoLightbox()" @keydown.window.arrow-left="previousPackagePhoto()" @keydown.window.arrow-right="nextPackagePhoto()"
         class="fixed inset-0 z-[130] flex cursor-zoom-out items-center justify-center bg-black/85 p-8 backdrop-blur-sm" style="display:none">
        <button type="button" @click.stop="closePhotoLightbox()" class="absolute right-4 top-4 z-20 rounded-full bg-black/45 p-3 text-white/80 transition hover:bg-black/70 hover:text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button type="button" x-show="packagePhotos(vendorPhotoPackage).length > 1" @click.stop="previousPackagePhoto()" class="absolute left-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/45 p-3 text-white/80 transition hover:bg-black/70 hover:text-white">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <img @click.stop :src="activePackagePhoto()?.url" :alt="activePackagePhoto()?.original_name || 'Package photo'" class="max-h-full max-w-full rounded-2xl object-contain shadow-2xl ring-1 ring-white/10">
        <button type="button" x-show="packagePhotos(vendorPhotoPackage).length > 1" @click.stop="nextPackagePhoto()" class="absolute right-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/45 p-3 text-white/80 transition hover:bg-black/70 hover:text-white">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div x-show="packagePhotos(vendorPhotoPackage).length > 1" class="absolute bottom-4 left-1/2 z-20 -translate-x-1/2 rounded-full bg-black/45 px-3 py-1.5 text-xs font-bold text-white/90" x-text="`${activePhotoIndex + 1} / ${packagePhotos(vendorPhotoPackage).length}`"></div>
    </div>
    </template>

    <template x-teleport="body">
    <div x-show="startSessionModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
        <div @click.stop class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-lg shadow-orange-500/25">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v8m4-4H8m10 8H6a2 2 0 01-2-2V6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900">Start Payment Session</h3>
                        <p class="mt-1 text-sm text-slate-500" x-text="agentOnly ? 'Enter your MoMo opening balance before you start recording recipient payments.' : 'Record the opening balance before the agent starts receiving recipient payments.'"></p>
                    </div>
                </div>
                <button type="button" @click="startSessionModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 p-5">
                <div x-show="activeWallets().length === 0" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-extrabold">No payment wallet assigned</p>
                    <p class="mt-1">Contact an admin to assign you an approved MoMo wallet before you start a payment session.</p>
                </div>
                <div x-show="activeWallets().length === 1" class="rounded-2xl border border-orange-100 bg-orange-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-orange-700">Payment wallet</p>
                    <p class="mt-1 text-sm font-black text-slate-900" x-text="walletLabel(activeWallets()[0] || {})"></p>
                </div>
                <div x-show="activeWallets().length > 1">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Wallet</label>
                    <select x-model="sessionForm.wallet_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">Select wallet</option>
                        <template x-for="wallet in activeWallets()" :key="wallet.id">
                            <option :value="wallet.id" x-text="walletLabel(wallet)"></option>
                        </template>
                    </select>
                </div>
                <div x-show="activeWallets().length > 0 && warehouses.length > 1">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Warehouse</label>
                    <select x-model="sessionForm.warehouse_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="" disabled>Select warehouse</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>
                </div>
                <div x-show="activeWallets().length > 0">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Opening balance</label>
                    <div class="flex items-center rounded-xl border-2 border-slate-200 bg-white px-3 transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                        <span class="mr-2 shrink-0 text-sm font-black text-slate-500">GHS</span>
                        <input type="number" step="0.01" x-model="sessionForm.opening_balance" placeholder="0.00" class="min-w-0 flex-1 border-0 bg-transparent py-3 text-xl font-black text-slate-900 outline-none">
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @click="startSessionModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                <button type="button" x-show="activeWallets().length > 0" @click="startSession()" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-base font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 sm:text-sm">Start Session</button>
            </div>
        </div>
    </div>
    </template>

    <template x-teleport="body">
    <div x-show="closeSessionModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
        <div @click.stop class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-800 to-slate-950 text-white shadow-lg shadow-slate-900/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900">Close Payment Session</h3>
                        <p class="mt-1 text-sm text-slate-500" x-text="closingSession?.wallet ? `Confirm closing balance for ${closingSession.wallet}.` : 'Confirm the closing balance for this payment session.'"></p>
                    </div>
                </div>
                <button type="button" @click="closeSessionModalOpen = false" class="shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 p-5">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Closing balance</label>
                    <div class="flex items-center rounded-xl border-2 border-slate-200 bg-white px-3 transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                        <span class="mr-2 shrink-0 text-sm font-black text-slate-500">GHS</span>
                        <input type="number" step="0.01" x-model="closeSessionForm.closing_balance" placeholder="0.00" class="min-w-0 flex-1 border-0 bg-transparent py-3 text-xl font-black text-slate-900 outline-none">
                    </div>
                    <p class="mt-2 text-xs font-semibold text-slate-500">
                        Calculated from opening balance plus recorded payments. Edit it if the actual MoMo balance is different.
                    </p>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Notes <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                    <textarea x-model="closeSessionForm.notes" rows="3" placeholder="Closing notes" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @click="closeSessionModalOpen = false" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-base font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:text-sm">Cancel</button>
                <button type="button" @click="closeSession()" class="rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-3 text-base font-black text-white shadow-lg shadow-slate-900/20 transition hover:border-slate-800 hover:bg-slate-800 sm:text-sm">Close Session</button>
            </div>
        </div>
    </div>
    </template>

    <?php if($canManageWallets): ?>
    <template x-teleport="body">
    <div x-show="walletModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" @click="walletAgentsOpen = false" style="display:none">
        <div @click.stop class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm5 4h4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-900" x-text="walletModalMode === 'edit' ? 'Edit Payment Wallet' : 'Add Payment Wallet'"></h3>
                        <p class="mt-1 text-sm text-slate-500">Create an approved wallet and assign the agents allowed to record payments into it.</p>
                    </div>
                </div>
                <button type="button" @click="walletModalOpen = false" class="rounded-xl border border-slate-200 p-2 text-slate-400 hover:text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-600">Wallet name</label>
                    <input x-model="walletForm.name" placeholder="e.g. Kumasi MoMo Main" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-600">Provider</label>
                    <select x-model="walletForm.provider" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <template x-for="provider in walletProviderOptions" :key="provider">
                            <option :value="provider" x-text="provider"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-600">Phone number</label>
                    <input x-model="walletForm.phone_number" placeholder="+233..." class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-bold text-slate-600">Account owner</label>
                    <input x-model="walletForm.account_owner" placeholder="Account owner" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    <p class="mt-1 text-[11px] text-slate-400">Registered name on the mobile money account.</p>
                </div>
                <div class="relative md:col-span-2" x-ref="walletAgentsSelect">
                    <label class="mb-1 block text-xs font-bold text-slate-600">Assigned agents</label>
                    <button type="button" @click.stop="walletAgentsOpen = !walletAgentsOpen; $nextTick(() => $refs.walletAgentSearch?.focus())"
                            class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-sm outline-none transition hover:border-slate-300 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <span :class="walletForm.user_ids.length ? 'text-slate-800' : 'text-slate-400'" x-text="selectedWalletAgentText()"></span>
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="walletAgentsOpen" @click.stop x-transition class="absolute z-[130] mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" style="display:none">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input x-ref="walletAgentSearch" x-model="walletAgentSearch" placeholder="Search agents..." class="w-full border-b border-slate-100 bg-slate-50 py-2.5 pl-9 pr-3 text-sm outline-none">
                        </div>
                        <div class="max-h-52 overflow-y-auto">
                            <template x-if="filteredWalletAgents().length === 0">
                                <p class="px-3 py-3 text-sm text-slate-400">No agents found.</p>
                            </template>
                            <template x-for="worker in filteredWalletAgents()" :key="worker.id">
                                <label class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm transition hover:bg-orange-50">
                                    <input type="checkbox" :checked="isWalletAgentSelected(worker.id)" @change="toggleWalletAgent(worker.id)" class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                    <span class="font-medium text-slate-700" x-text="worker.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2" x-show="warehouses.length > 1">
                    <label class="mb-1 block text-xs font-bold text-slate-600">Warehouse</label>
                    <select x-model="walletForm.warehouse_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">All warehouses</option>
                        <template x-for="wh in warehouses" :key="wh.id">
                            <option :value="wh.id" x-text="wh.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @click="walletModalOpen = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600">Cancel</button>
                <button type="button" @click="saveWallet()" class="rounded-xl bg-orange-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-orange-600/20 transition hover:bg-orange-700" x-text="walletModalMode === 'edit' ? 'Save Changes' : 'Save Wallet'"></button>
            </div>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <template x-teleport="body">
    <div x-show="confirmModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[120] flex min-h-screen items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none">
        <div @click.stop class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start gap-3 border-b border-slate-100 p-5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Delete Payment Wallet</h3>
                    <p class="mt-1 text-sm text-slate-500" x-text="confirmMessage"></p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 p-4">
                <button type="button" @click="confirmModalOpen = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600">Cancel</button>
                <button type="button" @click="deleteWallet()" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-rose-600/20 transition hover:bg-rose-700">Delete Wallet</button>
            </div>
        </div>
    </div>
    </template>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function recipientPaymentsPage() {
    return {
        config: {},
        currentUserId: null,
        canManageWallets: false,
        agentOnly: false,
        agentQueueOpen: true,
        openScannerAfterSessionStart: false,
        workers: [],
        wallets: [],
        walletRows: [],
        warehouses: [],
        tasks: [],
        sessions: [],
        sessionLoading: false,
        sessionMeta: { total: 0, from: 0, to: 0, current_page: 1, last_page: 1 },
        sessionSearch: '',
        sessionStatusFilter: '',
        sessionWalletFilter: '',
        sessionAgentFilter: '',
        sessionWarehouseFilter: '',
        sessionDateFrom: '',
        sessionDateTo: '',
        sessionDateRangePicker: null,
        sessionPerPage: 25,
        sessionSortBy: 'started_at',
        sessionSortDirection: 'desc',
        meta: { total: 0, from: 0, to: 0, current_page: 1, last_page: 1, per_page: 20 },
        summary: { pending: 0, paid: 0, expected: 0 },
        activeTab: 'all',
        search: '',
        taskStatusFilter: '',
        taskAgentFilter: '',
        taskPerPage: 20,
        taskSortBy: 'id',
        taskSortDirection: 'desc',
        scanCode: '',
        scanModalOpen: false,
        scannerActive: false,
        scannerStatus: '',
        scanModalMessage: '',
        scannerStream: null,
        scannerControls: null,
        scannerReader: null,
        scannerDetector: null,
        scannerFrame: null,
        scannerMisses: 0,
        scannerPending: false,
        scannerRejectedCodes: {},
        taskLocationResults: [],
        taskLocationSearchTimer: null,
        taskLocationCloseTimer: null,
        vendorPhotoModalOpen: false,
        vendorPhotoPackage: null,
        activePhotoIndex: 0,
        loading: false,
        selectedTaskIds: [],
        bulkAssignUserId: '',
        notice: { success: true, message: '' },
        taskModalOpen: false,
        activeTask: null,
        recipientEditing: false,
        taskForm: { outcome: 'answered', notes: '', amount: '', payment_wallet_id: '', payment_reference: '', payment_receipt_name: '', recipient_phone: '', delivery_town: '', assigned_to_user_id: '', override_reason: '' },
        startSessionModalOpen: false,
        sessionForm: { wallet_id: '', warehouse_id: '', opening_balance: '' },
        closeSessionModalOpen: false,
        closingSession: null,
        closeSessionForm: { closing_balance: '', notes: '' },
        walletModalOpen: false,
        walletModalMode: 'create',
        editingWallet: null,
        walletProviderOptions: ['MTN MoMo', 'Telecel Cash', 'AirtelTigo Cash'],
        walletAgentsOpen: false,
        walletAgentSearch: '',
        walletForm: { name: '', provider: 'MTN MoMo', phone_number: '', account_owner: '', warehouse_id: '', user_ids: [] },
        walletLoading: false,
        walletMeta: { total: 0, from: 0, to: 0, current_page: 1, last_page: 1 },
        walletSearch: '',
        walletStatusFilter: '',
        walletProviderFilter: '',
        walletWarehouseFilter: '',
        walletDateFrom: '',
        walletDateTo: '',
        walletDateRangePicker: null,
        walletPerPage: 25,
        walletSortBy: 'created_at',
        walletSortDirection: 'desc',
        walletColumns: [
            { key: 'name', label: 'Wallet', sortable: true },
            { key: 'provider', label: 'Provider', sortable: true },
            { key: 'phone_number', label: 'Phone', sortable: true },
            { key: 'account_owner', label: 'Account Owner', sortable: true },
            { key: 'warehouse', label: 'Warehouse', sortable: false },
            { key: 'assigned_agents', label: 'Assigned Agents', sortable: false },
            { key: 'recorded_amount', label: 'Recorded Amount', sortable: false },
            { key: 'payment_count', label: 'Payments', sortable: false },
            { key: 'last_payment_at', label: 'Last Payment', sortable: false },
            { key: 'history', label: 'History', sortable: false },
            { key: 'status', label: 'Status', sortable: false },
        ],
        walletVisibleColumns: {
            name: true,
            provider: true,
            phone_number: true,
            account_owner: true,
            warehouse: true,
            assigned_agents: true,
            recorded_amount: true,
            payment_count: true,
            last_payment_at: true,
            history: false,
            status: true,
        },
        taskColumns: [
            { key: 'group', label: 'Group', sortable: true },
            { key: 'batch', label: 'Batch', sortable: false },
            { key: 'package', label: 'Package', sortable: false },
            { key: 'recipient', label: 'Recipient', sortable: true },
            { key: 'method', label: 'Method', sortable: false },
            { key: 'fee', label: 'Fee', sortable: false },
            { key: 'status', label: 'Status', sortable: true },
            { key: 'assigned', label: 'Assigned', sortable: false },
        ],
        taskVisibleColumns: {
            group: true,
            batch: true,
            package: true,
            recipient: true,
            method: true,
            fee: true,
            status: true,
            assigned: true,
        },
        sessionColumns: [
            { key: 'agent', label: 'Agent', sortable: false },
            { key: 'wallet', label: 'Wallet', sortable: false },
            { key: 'provider', label: 'Provider', sortable: false },
            { key: 'phone', label: 'Phone', sortable: false },
            { key: 'warehouse', label: 'Warehouse', sortable: false },
            { key: 'opening_balance', label: 'Opening', sortable: true },
            { key: 'closing_balance', label: 'Closing', sortable: true },
            { key: 'expected_closing_balance', label: 'Expected', sortable: true },
            { key: 'variance', label: 'Variance', sortable: true },
            { key: 'started_at', label: 'Started', sortable: true },
            { key: 'closed_at', label: 'Closed', sortable: true },
            { key: 'status', label: 'Status', sortable: true },
        ],
        sessionVisibleColumns: {
            agent: true,
            wallet: true,
            provider: true,
            phone: true,
            warehouse: true,
            opening_balance: true,
            closing_balance: true,
            expected_closing_balance: true,
            variance: true,
            started_at: true,
            closed_at: true,
            status: true,
        },
        confirmModalOpen: false,
        confirmWallet: null,
        confirmMessage: '',
        _searchTimeout: null,
        _walletAgentClickHandler: null,

        init() {
            this.config = JSON.parse(this.$el.dataset.config);
            this.currentUserId = Number(this.$el.dataset.currentUserId || 0) || null;
            this.canManageWallets = JSON.parse(this.$el.dataset.canManageWallets || 'false');
            this.agentOnly = JSON.parse(this.$el.dataset.agentOnly || 'false');
            this.workers = JSON.parse(this.$el.dataset.workers || '[]');
            this.wallets = JSON.parse(this.$el.dataset.wallets || '[]');
            this.warehouses = JSON.parse(this.$el.dataset.warehouses || '[]');
            this.walletDateFrom = this.todayString();
            this.walletDateTo = this.todayString();
            this.sessionDateFrom = this.todayString();
            this.sessionDateTo = this.todayString();
            this.$nextTick(() => {
                this.initWalletDateRange();
                this.initSessionDateRange();
            });
            this._walletAgentClickHandler = (event) => {
                if (!this.walletAgentsOpen) return;
                const wrapper = this.$refs.walletAgentsSelect;
                if (wrapper && !wrapper.contains(event.target)) {
                    this.walletAgentsOpen = false;
                }
            };
            document.addEventListener('click', this._walletAgentClickHandler, true);
            this.refreshWalletOptions();
            this.activeTab = this.resolveInitialTab();
            this.loadActiveTab();
        },

        resolveInitialTab() {
            if (this.agentOnly) return 'mine';
            const params = new URLSearchParams(window.location.search);
            const aliases = {
                wallet: 'wallets',
                wallets: 'wallets',
                session: 'sessions',
                sessions: 'sessions',
                local: 'local_delivery',
                transfer: 'warehouse_transfer',
            };
            const requested = aliases[params.get('tab')] || params.get('tab') || 'all';
            return ['all', 'local_delivery', 'warehouse_transfer', 'mine', 'sessions', 'wallets'].includes(requested) ? requested : 'all';
        },

        setTab(tab) {
            if (this.agentOnly) tab = 'mine';
            this.activeTab = tab;
            this.selectedTaskIds = [];
            const url = new URL(window.location.href);
            if (tab === 'all') {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', tab);
            }
            window.history.pushState({}, '', url);
            this.loadActiveTab();
        },

        loadActiveTab() {
            if (this.agentOnly) {
                this.activeTab = 'mine';
                this.loadData(1);
                this.loadSessions(1);
                return;
            }
            if (this.activeTab === 'sessions') {
                this.loadSessions();
                return;
            }
            if (this.activeTab === 'wallets') {
                this.loadWallets(1);
                return;
            }
            this.loadData(1);
        },

        tabLinkClass(tab) {
            return this.activeTab === tab
                ? 'border-orange-600 text-orange-600'
                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-900';
        },

        queueTitle() {
            if (this.activeTab === 'local_delivery') return 'Local Delivery Payments';
            if (this.activeTab === 'warehouse_transfer') return 'Warehouse Transfer Payments';
            if (this.activeTab === 'mine') return 'Assigned to Me';
            return 'Recipient Payment Queue';
        },

        onSearch() {
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(() => this.loadData(1), 300);
        },

        async loadData(page = 1) {
            this.loading = true;
            const params = new URLSearchParams({
                page,
                per_page: this.taskPerPage,
                sort: this.taskSortBy,
                direction: this.taskSortDirection,
            });
            if (this.search) params.set('search', this.search);
            if (this.taskStatusFilter) params.set('status', this.taskStatusFilter);
            if (this.taskAgentFilter && this.activeTab !== 'mine') params.set('assigned_to_user_id', this.taskAgentFilter);
            if (this.activeTab === 'local_delivery' || this.activeTab === 'warehouse_transfer') params.set('group', this.activeTab);
            if (this.activeTab === 'mine') {
                params.set('assigned_to_me', '1');
                params.set('group_by_recipient', '1');
            }

            try {
                const res = await fetch(`${this.config.dataUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.tasks = json.data || [];
                this.meta = json.meta || this.meta;
                this.recalculateSummary();
            } catch (e) {
                this.toast(false, 'Failed to load recipient payment queue.');
            } finally {
                this.loading = false;
            }
        },

        recalculateSummary() {
            this.summary.pending = this.tasks.reduce((sum, t) => sum + (t.is_group ? Number(t.pending_count || 0) : (!['paid', 'waived', 'overridden'].includes(t.status) ? 1 : 0)), 0);
            this.summary.paid = this.tasks.reduce((sum, t) => sum + (t.is_group ? Number(t.paid_count || 0) : (t.status === 'paid' ? 1 : 0)), 0);
            this.summary.expected = this.tasks.reduce((sum, t) => sum + Number(t.fee_amount || 0), 0);
        },

        goPage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.loadData(page);
        },

        taskQueryParams(page = 1) {
            const params = new URLSearchParams({
                page,
                per_page: this.taskPerPage,
                sort: this.taskSortBy,
                direction: this.taskSortDirection,
            });
            if (this.search) params.set('search', this.search);
            if (this.taskStatusFilter) params.set('status', this.taskStatusFilter);
            if (this.taskAgentFilter && this.activeTab !== 'mine') params.set('assigned_to_user_id', this.taskAgentFilter);
            if (this.activeTab === 'local_delivery' || this.activeTab === 'warehouse_transfer') params.set('group', this.activeTab);
            if (this.activeTab === 'mine') {
                params.set('assigned_to_me', '1');
                params.set('group_by_recipient', '1');
            }
            return params;
        },

        openTask(task) {
            this.activeTask = task;
            const activeWallets = this.activeWallets();
            this.taskForm = {
                outcome: 'answered',
                notes: task.notes || '',
                amount: task.fee_amount ?? '',
                payment_wallet_id: task.payment_wallet_id || (activeWallets.length === 1 ? activeWallets[0].id : ''),
                payment_reference: task.payment_reference || '',
                payment_receipt_name: '',
                recipient_phone: task.recipient_phone || '',
                delivery_town: task.delivery_town || '',
                assigned_to_user_id: task.assigned_to_user_id || '',
                override_reason: '',
            };
            if (this.$refs.paymentReceiptInput) this.$refs.paymentReceiptInput.value = '';
            this.taskLocationResults = [];
            this.recipientEditing = false;
            this.taskModalOpen = true;
        },

        async openRecipientGroupForTask(task) {
            const phone = String(task?.recipient_phone || '').trim();
            if (!phone) {
                this.openTask(task);
                return;
            }

            const params = new URLSearchParams({
                page: 1,
                per_page: 1,
                assigned_to_me: '1',
                group_by_recipient: '1',
                search: phone,
            });

            try {
                const res = await fetch(`${this.config.dataUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                const group = (json.data || []).find(row => row.is_group && this.normalizedPhone(row.recipient_phone) === this.normalizedPhone(phone));
                this.openTask(group || task);
            } catch (e) {
                this.openTask(task);
            }
        },

        groupTaskIds(group) {
            return (group?.tasks || []).map(task => task.id);
        },

        groupPendingTaskIds(group) {
            return (group?.tasks || [])
                .filter(task => !['paid', 'waived', 'overridden'].includes(task.status))
                .map(task => task.id);
        },

        groupSummary(group) {
            const packageCount = Number(group?.package_count || 0);
            const pending = Number(group?.pending_count || 0);
            return `${packageCount} package${packageCount === 1 ? '' : 's'} · ${pending} pending · ${group?.recipient_phone || 'No phone'}`;
        },

        modalTasks() {
            if (!this.activeTask) return [];
            return this.activeTask.is_group ? (this.activeTask.tasks || []) : [this.activeTask];
        },

        singleModalTask() {
            const tasks = this.modalTasks();
            return tasks.length === 1 ? tasks[0] : null;
        },

        modalCanRelease() {
            return this.modalTasks().some(task => task.can_release);
        },

        paymentSubmitLabel() {
            return this.activeTask && this.feeStatusLabel(this.activeTask) === 'Paid' ? 'Save Payment' : 'Mark Paid';
        },

        packagePhotos(packageItem) {
            return packageItem?.vendor_photos || packageItem?.photos || [];
        },

        photoCountLabel(packageItem) {
            const count = this.packagePhotos(packageItem).length;
            return `${count} photo${count === 1 ? '' : 's'}`;
        },

        photoSourceLabel(packageItem) {
            if (!this.packagePhotos(packageItem).length) return 'No photos';
            return packageItem?.photo_source_label || 'Package photos';
        },

        receiptUrlFor(task) {
            return task?.payment_receipt_url || task?.tasks?.find(child => child.payment_receipt_url)?.payment_receipt_url || null;
        },

        receiptNameFor(task) {
            return task?.payment_receipt_name || task?.tasks?.find(child => child.payment_receipt_name)?.payment_receipt_name || '';
        },

        activePaymentReceiptUrl() {
            return this.receiptUrlFor(this.activeTask) || this.modalTasks().find(task => task.payment_receipt_url)?.payment_receipt_url || null;
        },

        activePaymentReceiptName() {
            return this.receiptNameFor(this.activeTask) || this.modalTasks().find(task => task.payment_receipt_name)?.payment_receipt_name || '';
        },

        openPaymentReceipt() {
            const url = this.activePaymentReceiptUrl();
            if (!url) return;
            this.vendorPhotoPackage = {
                vendor_photos: [{ url, original_name: this.activePaymentReceiptName() || 'MoMo receipt' }],
                photo_source_label: 'MoMo receipt',
            };
            this.activePhotoIndex = 0;
            this.vendorPhotoModalOpen = true;
        },

        openTaskReceipt(task) {
            const url = this.receiptUrlFor(task);
            if (!url) return;
            this.vendorPhotoPackage = {
                vendor_photos: [{ url, original_name: this.receiptNameFor(task) || 'MoMo receipt' }],
                photo_source_label: 'MoMo receipt',
            };
            this.activePhotoIndex = 0;
            this.vendorPhotoModalOpen = true;
        },

        activePackagePhoto() {
            return this.packagePhotos(this.vendorPhotoPackage)[this.activePhotoIndex] || null;
        },

        nextPackagePhoto() {
            const photos = this.packagePhotos(this.vendorPhotoPackage);
            if (photos.length < 2) return;
            this.activePhotoIndex = (this.activePhotoIndex + 1) % photos.length;
        },

        previousPackagePhoto() {
            const photos = this.packagePhotos(this.vendorPhotoPackage);
            if (photos.length < 2) return;
            this.activePhotoIndex = (this.activePhotoIndex - 1 + photos.length) % photos.length;
        },

        closePhotoLightbox() {
            this.vendorPhotoModalOpen = false;
            this.vendorPhotoPackage = null;
            this.activePhotoIndex = 0;
        },

        normalizedPhone(value) {
            return String(value || '').replace(/\D+/g, '');
        },

        recipientCallHref() {
            const phone = String(this.taskForm.recipient_phone || '').trim();
            return phone ? `tel:${phone}` : '#';
        },

        searchTaskLocation() {
            clearTimeout(this.taskLocationSearchTimer);
            clearTimeout(this.taskLocationCloseTimer);
            const query = String(this.taskForm.delivery_town || '').trim();
            if (query.length < 2) {
                this.taskLocationResults = [];
                return;
            }
            this.taskLocationSearchTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`${this.config.locationSearchUrl}?q=${encodeURIComponent(query)}`, { headers: { 'Accept': 'application/json' } });
                    const json = await res.json();
                    this.taskLocationResults = json.locations || [];
                } catch (e) {
                    this.taskLocationResults = [];
                }
            }, 250);
        },

        closeTaskLocationResults() {
            clearTimeout(this.taskLocationCloseTimer);
            this.taskLocationResults = [];
        },

        closeTaskLocationResultsSoon() {
            clearTimeout(this.taskLocationCloseTimer);
            this.taskLocationCloseTimer = setTimeout(() => {
                this.taskLocationResults = [];
            }, 150);
        },

        selectTaskLocation(location) {
            this.taskForm.delivery_town = location.display || location.name || '';
            clearTimeout(this.taskLocationCloseTimer);
            this.taskLocationResults = [];
        },

        openVendorPhotos(packageItem) {
            if (!this.packagePhotos(packageItem).length) return;
            this.vendorPhotoPackage = packageItem;
            this.activePhotoIndex = 0;
            this.vendorPhotoModalOpen = true;
        },

        handlePaymentReceiptChange(event) {
            const file = event.target.files?.[0] || null;
            if (!file) {
                this.taskForm.payment_receipt_name = '';
                return;
            }
            const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                event.target.value = '';
                this.taskForm.payment_receipt_name = '';
                this.toast(false, 'Upload a PNG, JPG or WEBP receipt screenshot.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                event.target.value = '';
                this.taskForm.payment_receipt_name = '';
                this.toast(false, 'Receipt screenshot must be 5MB or less.');
                return;
            }
            this.taskForm.payment_receipt_name = file.name;
        },

        async scanPackage() {
            if (!this.scanCode.trim()) return;
            this.scanModalMessage = '';
            const json = await this.post(this.config.scanUrl, { code: this.scanCode.trim() });
            if (!json.success) {
                this.scanModalMessage = json.message || 'Package not found.';
                return;
            }
            if (json.success) {
                await this.openScannedTask(json.task);
            }
        },

        async openScannedTask(scannedTask) {
            this.closeScanModal();
            this.scanCode = '';
            await this.loadData(1);
            if (this.activeTab === 'mine') {
                await this.openRecipientGroupForTask(scannedTask);
            } else {
                this.openTask(scannedTask);
            }
        },

        async releaseTask(task) {
            if (!task?.can_release) return;
            const url = this.config.releaseUrl.replace('__TASK__', task.id);
            const json = await this.post(url, {});
            this.toast(json.success, json.message || 'Release updated.');
            if (json.success) {
                await this.loadData(this.meta.current_page || 1);
            }
        },

        async releaseActiveTask() {
            const releasable = this.modalTasks().filter(task => task.can_release);
            if (!releasable.length) return;
            let last = { success: true, message: 'Package released.' };
            for (const task of releasable) {
                const url = this.config.releaseUrl.replace('__TASK__', task.id);
                last = await this.post(url, {});
                if (!last.success) break;
            }
            this.toast(last.success, last.message || 'Release updated.');
            if (last.success) {
                this.taskModalOpen = false;
                await this.loadData(this.meta.current_page || 1);
            }
        },

        openPrimaryAgentAction() {
            const open = this.openSession();
            if (!this.primaryWallet() && !open) {
                this.toast(false, 'Ask an admin to assign you an approved payment wallet first.');
                return;
            }
            if (open && !this.sessionStartedToday(open)) {
                this.openCloseSession(open);
                return;
            }
            if (open && !this.primaryWallet()) {
                this.toast(false, 'Your wallet assignment was removed. Close your open session or contact admin.');
                this.openCloseSession(open);
                return;
            }
            if (open) {
                this.openScanModal();
                return;
            }
            this.openStartSession(true);
        },

        openScanModal() {
            this.scanCode = '';
            this.scannerStatus = '';
            this.scanModalMessage = '';
            this.scannerPending = false;
            this.scannerRejectedCodes = {};
            this.scanModalOpen = true;
            this.$nextTick(() => this.$refs.scanCodeInput?.focus());
        },

        closeScanModal() {
            this.stopScanner();
            this.scanModalOpen = false;
        },

        async startScanner() {
            if (window.ZXingBrowser?.BrowserMultiFormatReader || 'BarcodeDetector' in window) {
                await this.startZxingScanner();
                return;
            }

            this.scannerStatus = 'Camera scanning is not supported in this browser. Use manual entry below.';
            this.$nextTick(() => this.$refs.scanCodeInput?.focus());
        },

        async startZxingScanner() {
            if (!navigator.mediaDevices?.getUserMedia) {
                this.scannerStatus = 'Camera access is not available. Use manual entry below.';
                this.$nextTick(() => this.$refs.scanCodeInput?.focus());
                return;
            }

            try {
                this.stopScanner();
                this.scannerStatus = 'Starting camera...';
                this.scanModalMessage = '';
                const video = this.$refs.scanVideo;
                video.classList.remove('hidden');
                this.scannerActive = true;

                if (window.ZXingBrowser?.BrowserMultiFormatReader) {
                    const hints = new Map();
                    hints.set(window.ZXingBrowser.DecodeHintType.POSSIBLE_FORMATS, this.zxingBarcodeFormats());
                    hints.set(window.ZXingBrowser.DecodeHintType.TRY_HARDER, true);
                    hints.set(window.ZXingBrowser.DecodeHintType.ENABLE_CODE_39_EXTENDED_MODE, true);

                    this.scannerReader = new window.ZXingBrowser.BrowserMultiFormatReader(hints, {
                        delayBetweenScanAttempts: 80,
                        delayBetweenScanSuccess: 500,
                    });
                }

                if ('BarcodeDetector' in window) {
                    this.scannerDetector = await this.createBarcodeDetector();
                }

                const constraints = {
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        advanced: [{ focusMode: 'continuous' }],
                    },
                };
                this.scannerStream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = this.scannerStream;
                video.setAttribute('playsinline', 'true');
                video.muted = true;
                await video.play();

                this.scannerMisses = 0;
                this.scannerStatus = 'Scanning barcode...';
                this.scannerFrame = requestAnimationFrame(() => this.scanZxingFrame());
            } catch (e) {
                console.error('ZXing scanner failed', e);
                this.scannerStatus = 'Camera scanner could not start. Use manual entry below.';
                this.stopScanner();
                this.$nextTick(() => this.$refs.scanCodeInput?.focus());
            }
        },

        zxingBarcodeFormats() {
            const formats = window.ZXingBrowser?.BarcodeFormat || {};
            return [
                formats.CODE_128,
                formats.CODE_39,
                formats.CODE_93,
                formats.CODABAR,
                formats.ITF,
                formats.EAN_13,
                formats.EAN_8,
                formats.UPC_A,
                formats.UPC_E,
                formats.RSS_14,
                formats.RSS_EXPANDED,
                formats.PDF_417,
                formats.QR_CODE,
                formats.DATA_MATRIX,
                formats.AZTEC,
            ].filter(format => format !== undefined && format !== null);
        },

        async createBarcodeDetector() {
            if (!('BarcodeDetector' in window)) return null;

            const desiredFormats = ['code_128', 'code_39', 'code_93', 'codabar', 'itf', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code', 'pdf417', 'data_matrix', 'aztec'];
            let formats = desiredFormats;

            try {
                if (BarcodeDetector.getSupportedFormats) {
                    const supported = await BarcodeDetector.getSupportedFormats();
                    formats = desiredFormats.filter(format => supported.includes(format));
                }
            } catch (e) {
                formats = desiredFormats;
            }

            try {
                return new BarcodeDetector({ formats });
            } catch (e) {
                try {
                    return new BarcodeDetector();
                } catch (error) {
                    return null;
                }
            }
        },

        async scanZxingFrame() {
            if (!this.scannerActive) return;
            if (this.scannerPending) {
                this.scannerFrame = requestAnimationFrame(() => this.scanZxingFrame());
                return;
            }

            const video = this.$refs.scanVideo;
            const canvas = this.$refs.scanCanvas;
            const reader = this.scannerReader;
            const detector = this.scannerDetector;

            if (!video || !canvas || (!reader && !detector) || video.readyState < 2 || !video.videoWidth || !video.videoHeight) {
                this.scannerFrame = requestAnimationFrame(() => this.scanZxingFrame());
                return;
            }

            const videoWidth = video.videoWidth;
            const videoHeight = video.videoHeight;
            const context = canvas.getContext('2d', { willReadFrequently: true });
            const crops = [
                { width: 1, height: 1, y: 0.50 },
                { width: 0.92, height: 0.42, y: 0.50 },
                { width: 0.96, height: 0.30, y: 0.50 },
                { width: 0.92, height: 0.46, y: 0.45 },
            ];

            for (const crop of crops) {
                const cropWidth = Math.floor(videoWidth * crop.width);
                const cropHeight = Math.floor(videoHeight * crop.height);
                const sourceX = Math.max(0, Math.floor((videoWidth - cropWidth) / 2));
                const centerY = Math.floor(videoHeight * crop.y);
                const sourceY = Math.max(0, Math.min(videoHeight - cropHeight, Math.floor(centerY - (cropHeight / 2))));

                canvas.width = cropWidth;
                canvas.height = cropHeight;
                context.drawImage(video, sourceX, sourceY, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);

                const nativeText = await this.detectWithNativeBarcodeDetector(canvas);
                if (nativeText) {
                    if (await this.submitDetectedScanCode(nativeText)) return;
                }

                const zxingText = this.detectWithZxing(canvas);
                if (zxingText) {
                    if (await this.submitDetectedScanCode(zxingText)) return;
                }
            }

            this.scannerMisses += 1;
            if (this.scannerMisses === 90) {
                this.scannerStatus = 'Move closer and keep the bars sharp.';
            } else if (this.scannerMisses === 180) {
                this.scannerStatus = 'Still scanning. Try manual entry if it does not pick.';
            }

            this.scannerFrame = requestAnimationFrame(() => this.scanZxingFrame());
        },

        async submitDetectedScanCode(rawValue) {
            const code = this.normalizedScanCode(rawValue);

            if (!code) return false;

            const lastRejectedAt = this.scannerRejectedCodes[code] || 0;
            if (Date.now() - lastRejectedAt < 4000) return false;

            this.scannerPending = true;
            this.scanCode = code;
            this.scannerStatus = `Checking ${code}...`;
            this.scanModalMessage = '';

            const json = await this.post(this.config.scanUrl, { code });
            this.scannerPending = false;

            if (!this.scannerActive) return false;

            if (!json.success) {
                this.scannerRejectedCodes[code] = Date.now();
                this.scanModalMessage = `${json.message || 'No package found for this code.'} Last read: ${code}. Still scanning...`;
                this.scannerStatus = 'Scanning for another barcode...';
                return false;
            }

            await this.openScannedTask(json.task);
            return true;
        },

        normalizedScanCode(rawValue) {
            return String(rawValue || '')
                .trim()
                .toUpperCase()
                .replace(/\s+/g, '');
        },

        async detectWithNativeBarcodeDetector(canvas) {
            if (!this.scannerDetector) return '';

            try {
                const codes = await this.scannerDetector.detect(canvas);
                return codes?.[0]?.rawValue || '';
            } catch (e) {
                return '';
            }
        },

        detectWithZxing(canvas) {
            if (!this.scannerReader) return '';

            try {
                const result = this.scannerReader.decodeFromCanvas(canvas);
                return result?.getText?.() || result?.text || '';
            } catch (e) {
                return '';
            }
        },

        stopScanner() {
            if (this.scannerControls) {
                this.scannerControls.stop?.();
                this.scannerControls = null;
            }
            if (this.scannerReader) {
                this.scannerReader.reset?.();
                this.scannerReader = null;
            }
            this.scannerDetector = null;
            if (this.scannerFrame) {
                cancelAnimationFrame(this.scannerFrame);
                this.scannerFrame = null;
            }
            if (this.scannerStream) {
                this.scannerStream.getTracks().forEach(track => track.stop());
                this.scannerStream = null;
            }
            if (this.$refs.scanVideo) {
                this.$refs.scanVideo.pause?.();
                this.$refs.scanVideo.srcObject = null;
                this.$refs.scanVideo.classList.add('hidden');
            }
            this.scannerMisses = 0;
            this.scannerPending = false;
            this.scannerActive = false;
        },

        async bulkAssign() {
            const json = await this.post(this.config.bulkAssignUrl, { task_ids: this.selectedTaskIds, user_id: this.bulkAssignUserId });
            this.toast(json.success, json.message || 'Assignment updated.');
            if (json.success) {
                this.selectedTaskIds = [];
                await this.loadData(this.meta.current_page);
            }
        },

        async assignActiveTask() {
            if (!this.activeTask || !this.taskForm.assigned_to_user_id) return;
            const url = this.config.assignUrl.replace('__TASK__', this.activeTask.id);
            const json = await this.post(url, { user_id: this.taskForm.assigned_to_user_id });
            this.toast(json.success, json.message || 'Assignment updated.');
            if (json.success) await this.loadData(this.meta.current_page);
        },

        async logCall() {
            if (!this.activeTask) return;
            const isGroup = this.activeTask.is_group;
            const url = isGroup ? this.config.groupLogCallUrl : this.config.logCallUrl.replace('__TASK__', this.activeTask.id);
            const payload = { outcome: this.taskForm.outcome, notes: this.taskForm.notes };
            if (isGroup) payload.task_ids = this.groupTaskIds(this.activeTask);
            const json = await this.post(url, payload);
            this.toast(json.success, json.message || 'Call logged.');
            if (json.success) await this.loadData(this.meta.current_page);
        },

        async saveRecipientDetails() {
            if (!this.activeTask) return;
            const taskIds = this.modalTasks().map(task => task.id);
            const details = await this.post(this.config.groupUpdateDetailsUrl, {
                task_ids: taskIds,
                recipient_phone: this.taskForm.recipient_phone,
                delivery_town: this.taskForm.delivery_town,
            });
            if (!details.success) {
                this.toast(false, details.message || 'Could not save recipient details.');
                return;
            }
            const call = await this.post(this.config.groupLogCallUrl, {
                task_ids: taskIds,
                outcome: this.taskForm.outcome,
                notes: this.taskForm.notes,
            });
            this.toast(call.success, call.message || 'Saved.');
            if (call.success) await this.loadData(this.meta.current_page);
        },

        async saveRecipientContactDetails() {
            if (!this.activeTask) return;
            const taskIds = this.modalTasks().map(task => task.id);
            const json = await this.post(this.config.groupUpdateDetailsUrl, {
                task_ids: taskIds,
                recipient_phone: this.taskForm.recipient_phone,
                delivery_town: this.taskForm.delivery_town,
            });

            this.toast(json.success, json.message || 'Recipient details saved.');
            if (!json.success) return;

            this.recipientEditing = false;
            const applyDetails = (task) => {
                task.recipient_phone = this.taskForm.recipient_phone;
                task.delivery_town = this.taskForm.delivery_town;
            };
            applyDetails(this.activeTask);
            this.modalTasks().forEach(applyDetails);
            await this.loadData(this.meta.current_page);
        },

        async saveFee() {
            if (!this.activeTask || this.activeTask.is_group) return;
            const url = this.config.feeUrl.replace('__TASK__', this.activeTask.id);
            const json = await this.post(url, { amount: this.taskForm.amount, notes: this.taskForm.notes });
            this.toast(json.success, json.message || 'Fee saved.');
            if (json.success) await this.loadData(this.meta.current_page);
        },

        async markPaid() {
            if (!this.activeTask) return;
            const amount = Number(this.taskForm.amount || 0);
            if (!amount || amount <= 0) {
                this.toast(false, 'Enter the delivery fee before marking payment as paid.');
                return;
            }
            if (!this.taskForm.outcome) {
                this.toast(false, 'Select the call result before marking payment as paid.');
                return;
            }
            if (!this.taskForm.payment_wallet_id) {
                this.toast(false, 'Select the payment wallet before marking payment as paid.');
                return;
            }
            const url = this.config.groupMarkPaidUrl;
            const payload = new FormData();
            this.modalTasks().forEach(task => payload.append('task_ids[]', task.id));
            payload.append('amount', this.taskForm.amount);
            payload.append('recipient_phone', this.taskForm.recipient_phone || '');
            payload.append('delivery_town', this.taskForm.delivery_town || '');
            payload.append('payment_wallet_id', this.taskForm.payment_wallet_id);
            payload.append('payment_reference', this.taskForm.payment_reference || '');
            payload.append('notes', this.taskForm.notes || '');
            payload.append('outcome', this.taskForm.outcome || 'answered');
            const receipt = this.$refs.paymentReceiptInput?.files?.[0] || null;
            if (receipt) payload.append('payment_receipt', receipt);
            const json = await this.postForm(url, payload);
            this.toast(json.success, json.message || 'Payment updated.');
            if (json.success) {
                this.taskModalOpen = false;
                await this.loadData(this.meta.current_page);
                await this.loadSessions();
            }
        },

        async overrideTask() {
            if (!this.activeTask) return;
            const url = this.config.overrideUrl.replace('__TASK__', this.activeTask.id);
            const json = await this.post(url, { reason: this.taskForm.override_reason });
            this.toast(json.success, json.message || 'Override updated.');
            if (json.success) {
                this.taskModalOpen = false;
                await this.loadData(this.meta.current_page);
            }
        },

        walletQueryParams(page = 1) {
            const params = new URLSearchParams({
                page,
                per_page: this.walletPerPage,
                sort: this.walletSortBy,
                direction: this.walletSortDirection,
            });
            if (this.walletSearch) params.set('search', this.walletSearch);
            if (this.walletStatusFilter) params.set('status', this.walletStatusFilter);
            if (this.walletProviderFilter) params.set('provider', this.walletProviderFilter);
            if (this.walletWarehouseFilter) params.set('wallet_warehouse_id', this.walletWarehouseFilter);
            if (this.walletDateFrom) params.set('date_from', this.walletDateFrom);
            if (this.walletDateTo) params.set('date_to', this.walletDateTo);
            return params;
        },

        async loadWallets(page = 1) {
            if (page < 1) return;
            this.walletLoading = true;
            try {
                const res = await fetch(`${this.config.walletsUrl}?${this.walletQueryParams(page).toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.walletRows = json.data || [];
                this.walletMeta = json.meta || this.walletMeta;
            } catch (e) {
                this.toast(false, 'Failed to load payment wallets.');
            } finally {
                this.walletLoading = false;
            }
        },

        async refreshWalletOptions() {
            try {
                const res = await fetch(`${this.config.walletsUrl}?options=1`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.wallets = json.data || [];
            } catch (e) {
                this.toast(false, 'Failed to refresh wallet options.');
            }
        },

        initWalletDateRange() {
            if (!this.$refs.walletPaymentRange) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                const $input = window.$(this.$refs.walletPaymentRange);
                if ($input.data('daterangepicker')) return;

                const start = window.moment(this.walletDateFrom || this.todayString(), 'YYYY-MM-DD');
                const end = window.moment(this.walletDateTo || this.todayString(), 'YYYY-MM-DD');

                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'right',
                    startDate: start,
                    endDate: end,
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: 'Clear',
                    },
                    ranges: {
                        'Today': [window.moment(), window.moment()],
                        'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                        'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                        'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                        'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                    },
                });

                $input.val(`${this.walletDateFrom} - ${this.walletDateTo}`);

                $input.on('apply.daterangepicker', (ev, picker) => {
                    this.walletDateFrom = picker.startDate.format('YYYY-MM-DD');
                    this.walletDateTo = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.walletDateFrom} - ${this.walletDateTo}`);
                    this.loadWallets(1);
                });

                $input.on('cancel.daterangepicker', () => {
                    this.clearWalletDateFilter();
                });

                this.walletDateRangePicker = $input.data('daterangepicker');
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

            const waitFor = (test, timeoutMs = 5000) => new Promise((resolve, reject) => {
                const started = Date.now();
                const tick = () => {
                    if (test()) return resolve();
                    if (Date.now() - started > timeoutMs) return reject(new Error('Timed out loading date range dependency.'));
                    setTimeout(tick, 25);
                };
                tick();
            });

            const loadScript = (id, src, ready) => new Promise((resolve, reject) => {
                const existing = document.getElementById(id);
                if (existing) {
                    waitFor(ready).then(resolve).catch(reject);
                    return;
                }
                const script = document.createElement('script');
                script.id = id;
                script.src = src;
                script.onload = () => waitFor(ready).then(resolve).catch(reject);
                script.onerror = () => reject(new Error(`Failed to load ${src}`));
                document.body.appendChild(script);
            });

            loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js', () => !!(window.jQuery?.fn || window.$?.fn))
                .then(() => {
                    window.$ = window.jQuery = window.jQuery || window.$;
                    return loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js', () => !!window.moment);
                })
                .then(() => {
                    window.$ = window.jQuery = window.jQuery || window.$;
                    return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', () => !!window.$?.fn?.daterangepicker);
                })
                .then(setupPicker)
                .catch(() => this.toast(false, 'Failed to load date range picker.'));
        },

        clearWalletDateFilter() {
            this.walletDateFrom = this.todayString();
            this.walletDateTo = this.todayString();
            if (this.walletDateRangePicker) {
                this.walletDateRangePicker.setStartDate(window.moment(this.walletDateFrom, 'YYYY-MM-DD'));
                this.walletDateRangePicker.setEndDate(window.moment(this.walletDateTo, 'YYYY-MM-DD'));
            }
            if (this.$refs.walletPaymentRange) {
                this.$refs.walletPaymentRange.value = `${this.walletDateFrom} - ${this.walletDateTo}`;
            }
            this.loadWallets(1);
        },

        openWalletModal(wallet = null) {
            this.walletModalMode = wallet ? 'edit' : 'create';
            this.editingWallet = wallet;
            this.walletForm = {
                name: wallet?.name || '',
                provider: wallet?.provider || 'MTN MoMo',
                phone_number: wallet?.phone_number || '',
                account_owner: wallet?.account_owner || '',
                warehouse_id: wallet?.warehouse_id || (this.warehouses.length === 1 ? this.warehouses[0].id : ''),
                user_ids: (wallet?.assigned_users || []).map(user => Number(user.id)),
            };
            this.walletAgentSearch = '';
            this.walletAgentsOpen = false;
            this.walletModalOpen = true;
        },

        async saveWallet() {
            const payload = { ...this.walletForm };
            if (this.warehouses.length === 1) payload.warehouse_id = this.warehouses[0].id;
            const url = this.walletModalMode === 'edit'
                ? this.config.walletUpdateUrl.replace('__WALLET__', this.editingWallet.id)
                : this.config.walletStoreUrl;
            const json = await this.post(url, payload);
            this.toast(json.success, json.message || 'Wallet saved.');
            if (json.success) {
                this.walletModalOpen = false;
                this.walletModalMode = 'create';
                this.editingWallet = null;
                this.walletForm = { name: '', provider: 'MTN MoMo', phone_number: '', account_owner: '', warehouse_id: '', user_ids: [] };
                this.walletAgentSearch = '';
                this.walletAgentsOpen = false;
                await this.loadWallets(this.walletMeta.current_page || 1);
                await this.refreshWalletOptions();
            }
        },

        confirmWalletDelete(wallet) {
            this.confirmWallet = wallet;
            this.confirmMessage = `Delete ${wallet.name}? This is only allowed because the wallet has no payment history.`;
            this.confirmModalOpen = true;
        },

        async deleteWallet() {
            if (!this.confirmWallet) return;
            const url = this.config.walletDeleteUrl.replace('__WALLET__', this.confirmWallet.id);
            const json = await this.post(url, {});
            this.toast(json.success, json.message || 'Wallet deleted.');
            if (json.success) {
                this.confirmModalOpen = false;
                this.confirmWallet = null;
                await this.loadWallets(this.walletMeta.current_page || 1);
                await this.refreshWalletOptions();
            }
        },

        async setWalletStatus(wallet, isActive) {
            const url = this.config.walletStatusUrl.replace('__WALLET__', wallet.id);
            const json = await this.post(url, { is_active: isActive });
            this.toast(json.success, json.message || 'Wallet status updated.');
            if (json.success) {
                await this.loadWallets(this.walletMeta.current_page || 1);
                await this.refreshWalletOptions();
            }
        },

        sessionQueryParams(page = 1) {
            const params = new URLSearchParams({
                page,
                per_page: this.sessionPerPage,
                sort: this.sessionSortBy,
                direction: this.sessionSortDirection,
            });
            if (this.sessionSearch) params.set('search', this.sessionSearch);
            if (this.sessionStatusFilter) params.set('status', this.sessionStatusFilter);
            if (this.sessionWalletFilter) params.set('wallet_id', this.sessionWalletFilter);
            if (this.sessionAgentFilter) params.set('agent_id', this.sessionAgentFilter);
            if (this.sessionWarehouseFilter) params.set('session_warehouse_id', this.sessionWarehouseFilter);
            if (this.sessionDateFrom) params.set('date_from', this.sessionDateFrom);
            if (this.sessionDateTo) params.set('date_to', this.sessionDateTo);
            if (this.agentOnly) params.set('include_open', '1');
            return params;
        },

        primaryWallet() {
            const assigned = this.activeWallets();
            return assigned[0] || null;
        },

        assignedWalletHeading() {
            const assigned = this.activeWallets();
            if (assigned.length === 0) return 'No wallet assigned';
            if (assigned.length === 1) return assigned[0].name;
            return `${assigned.length} wallets assigned`;
        },

        assignedWalletHelpText() {
            const assigned = this.activeWallets();
            if (assigned.length === 0) {
                return 'Contact an admin to assign you an approved MoMo wallet before recording payments.';
            }
            if (assigned.length === 1) {
                return 'Tell recipients to pay into this wallet before you mark payment as paid.';
            }
            return 'Choose the correct wallet when starting a session or recording payment.';
        },

        openSession() {
            return (this.sessions || []).find(session => session.status === 'open') || null;
        },

        sessionStartedToday(session) {
            if (!session?.started_at) return false;
            return String(session.started_at).slice(0, 10) === this.todayString();
        },

        todaySession() {
            return this.openSession() || (this.sessions || [])[0] || null;
        },

        agentRecordedToday() {
            return (this.sessions || []).reduce((sum, session) => {
                if (session.status === 'open') {
                    return sum + Math.max(0, Number(session.expected_closing_balance || 0) - Number(session.opening_balance || 0));
                }
                return sum;
            }, 0);
        },

        async loadSessions(page = 1) {
            if (page < 1) return;
            this.sessionLoading = true;
            try {
                const res = await fetch(`${this.config.sessionsUrl}?${this.sessionQueryParams(page).toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.sessions = json.data || [];
                this.sessionMeta = json.meta || this.sessionMeta;
            } catch (e) {
                this.toast(false, 'Failed to load payment sessions.');
            } finally {
                this.sessionLoading = false;
            }
        },

        initSessionDateRange() {
            if (!this.$refs.sessionDateRange) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                const $input = window.$(this.$refs.sessionDateRange);
                if ($input.data('daterangepicker')) return;

                const start = window.moment(this.sessionDateFrom || this.todayString(), 'YYYY-MM-DD');
                const end = window.moment(this.sessionDateTo || this.todayString(), 'YYYY-MM-DD');

                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'right',
                    startDate: start,
                    endDate: end,
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: 'Clear',
                    },
                    ranges: {
                        'Today': [window.moment(), window.moment()],
                        'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                        'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                        'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                        'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                    },
                });

                $input.val(`${this.sessionDateFrom} - ${this.sessionDateTo}`);

                $input.on('apply.daterangepicker', (ev, picker) => {
                    this.sessionDateFrom = picker.startDate.format('YYYY-MM-DD');
                    this.sessionDateTo = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.sessionDateFrom} - ${this.sessionDateTo}`);
                    this.loadSessions(1);
                });

                $input.on('cancel.daterangepicker', () => {
                    this.clearSessionDateFilter();
                });

                this.sessionDateRangePicker = $input.data('daterangepicker');
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

            const waitFor = (test, timeoutMs = 5000) => new Promise((resolve, reject) => {
                const started = Date.now();
                const tick = () => {
                    if (test()) return resolve();
                    if (Date.now() - started > timeoutMs) return reject(new Error('Timed out loading date range dependency.'));
                    setTimeout(tick, 25);
                };
                tick();
            });

            const loadScript = (id, src, ready) => new Promise((resolve, reject) => {
                const existing = document.getElementById(id);
                if (existing) {
                    waitFor(ready).then(resolve).catch(reject);
                    return;
                }
                const script = document.createElement('script');
                script.id = id;
                script.src = src;
                script.onload = () => waitFor(ready).then(resolve).catch(reject);
                script.onerror = () => reject(new Error(`Failed to load ${src}`));
                document.body.appendChild(script);
            });

            loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js', () => !!(window.jQuery?.fn || window.$?.fn))
                .then(() => {
                    window.$ = window.jQuery = window.jQuery || window.$;
                    return loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js', () => !!window.moment);
                })
                .then(() => {
                    window.$ = window.jQuery = window.jQuery || window.$;
                    return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', () => !!window.$?.fn?.daterangepicker);
                })
                .then(setupPicker)
                .catch(() => this.toast(false, 'Failed to load date range picker.'));
        },

        clearSessionDateFilter() {
            this.sessionDateFrom = this.todayString();
            this.sessionDateTo = this.todayString();
            if (this.sessionDateRangePicker) {
                this.sessionDateRangePicker.setStartDate(window.moment(this.sessionDateFrom, 'YYYY-MM-DD'));
                this.sessionDateRangePicker.setEndDate(window.moment(this.sessionDateTo, 'YYYY-MM-DD'));
            }
            if (this.$refs.sessionDateRange) {
                this.$refs.sessionDateRange.value = `${this.sessionDateFrom} - ${this.sessionDateTo}`;
            }
            this.loadSessions(1);
        },

        openStartSession(openScannerAfterStart = false) {
            const activeWallets = this.activeWallets();
            this.sessionForm = {
                wallet_id: activeWallets.length === 1 ? activeWallets[0].id : '',
                warehouse_id: this.warehouses.length === 1 ? this.warehouses[0].id : '',
                opening_balance: '',
            };
            this.openScannerAfterSessionStart = openScannerAfterStart;
            this.startSessionModalOpen = true;
        },

        async startSession() {
            const activeWallets = this.activeWallets();
            if (!activeWallets.length) {
                this.toast(false, 'Ask an admin to assign you an approved payment wallet first.');
                return;
            }
            const payload = {
                payment_wallet_id: activeWallets.length === 1 ? activeWallets[0].id : this.sessionForm.wallet_id,
                warehouse_id: this.sessionForm.warehouse_id || (this.warehouses[0]?.id || ''),
                opening_balance: this.sessionForm.opening_balance,
            };
            const shouldOpenScanner = this.openScannerAfterSessionStart;
            const json = await this.post(this.config.sessionStoreUrl, payload);
            if (!json.success || !(this.agentOnly && shouldOpenScanner)) {
                this.toast(json.success, json.message || 'Session started.');
            }
            if (json.success) {
                this.startSessionModalOpen = false;
                this.sessionForm = { wallet_id: '', warehouse_id: '', opening_balance: '' };
                await this.loadSessions(this.sessionMeta.current_page || 1);
                if (shouldOpenScanner) {
                    this.openScannerAfterSessionStart = false;
                    this.$nextTick(() => this.openScanModal());
                }
            }
        },

        openCloseSession(session) {
            this.closingSession = session;
            this.closeSessionForm = {
                closing_balance: this.defaultClosingBalance(session),
                notes: '',
            };
            this.closeSessionModalOpen = true;
        },

        defaultClosingBalance(session) {
            if (!session) return '';
            const expected = session.expected_closing_balance;
            if (expected !== null && expected !== undefined && expected !== '') {
                return Number(expected).toFixed(2);
            }

            return Number(session.opening_balance || 0).toFixed(2);
        },

        async closeSession() {
            const url = this.config.sessionCloseUrl.replace('__SESSION__', this.closingSession.id);
            const json = await this.post(url, this.closeSessionForm);
            this.toast(json.success, json.message || 'Session closed.');
            if (json.success) {
                this.closeSessionModalOpen = false;
                await this.loadSessions(this.sessionMeta.current_page || 1);
            }
        },

        async post(url, payload) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    },
                    body: JSON.stringify(payload),
                });
                return await res.json();
            } catch (e) {
                return { success: false, message: 'Request failed. Please try again.' };
            }
        },

        async postForm(url, payload) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    },
                    body: payload,
                });
                return await res.json();
            } catch (e) {
                return { success: false, message: 'Request failed. Please try again.' };
            }
        },

        walletSort(column) {
            const config = this.walletColumns.find(col => col.key === column);
            if (!config?.sortable) return;
            if (this.walletSortBy === column) {
                this.walletSortDirection = this.walletSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.walletSortBy = column;
                this.walletSortDirection = 'asc';
            }
            this.loadWallets(1);
        },

        toggleWalletColumn(key) {
            this.walletVisibleColumns[key] = !this.walletVisibleColumns[key];
        },

        taskSort(column) {
            const config = this.taskColumns.find(col => col.key === column);
            if (!config?.sortable) return;
            const sortMap = {
                group: 'payment_group',
                recipient: 'recipient_name',
                status: 'status',
            };
            const sortKey = sortMap[column] || column;
            if (this.taskSortBy === sortKey) {
                this.taskSortDirection = this.taskSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.taskSortBy = sortKey;
                this.taskSortDirection = 'asc';
            }
            this.loadData(1);
        },

        toggleTaskColumn(key) {
            this.taskVisibleColumns[key] = !this.taskVisibleColumns[key];
        },

        sessionSort(column) {
            const config = this.sessionColumns.find(col => col.key === column);
            if (!config?.sortable) return;
            if (this.sessionSortBy === column) {
                this.sessionSortDirection = this.sessionSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sessionSortBy = column;
                this.sessionSortDirection = 'asc';
            }
            this.loadSessions(1);
        },

        toggleSessionColumn(key) {
            this.sessionVisibleColumns[key] = !this.sessionVisibleColumns[key];
        },

        sessionFirstPage() {
            if (this.sessionMeta.current_page !== 1) this.loadSessions(1);
        },

        sessionPreviousPage() {
            if (this.sessionMeta.current_page > 1) this.loadSessions(this.sessionMeta.current_page - 1);
        },

        sessionNextPage() {
            if (this.sessionMeta.current_page < this.sessionMeta.last_page) this.loadSessions(this.sessionMeta.current_page + 1);
        },

        sessionLastPage() {
            if (this.sessionMeta.current_page !== this.sessionMeta.last_page) this.loadSessions(this.sessionMeta.last_page);
        },

        walletFirstPage() {
            if (this.walletMeta.current_page !== 1) this.loadWallets(1);
        },

        walletPreviousPage() {
            if (this.walletMeta.current_page > 1) this.loadWallets(this.walletMeta.current_page - 1);
        },

        walletNextPage() {
            if (this.walletMeta.current_page < this.walletMeta.last_page) this.loadWallets(this.walletMeta.current_page + 1);
        },

        walletLastPage() {
            if (this.walletMeta.current_page !== this.walletMeta.last_page) this.loadWallets(this.walletMeta.last_page);
        },

        exportWallets(format) {
            const params = this.walletQueryParams(this.walletMeta.current_page || 1);
            params.set('format', format);
            if (format === 'csv') {
                fetch(`${this.config.walletExportUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(json => this.downloadCsv(json.data || [], 'payment_wallets.csv'))
                    .catch(() => this.toast(false, 'Failed to export wallets.'));
                return;
            }
            window.location.href = `${this.config.walletExportUrl}?${params.toString()}`;
        },

        exportTasks(format) {
            const params = this.taskQueryParams(this.meta.current_page || 1);
            params.set('format', format);
            if (format === 'csv') {
                fetch(`${this.config.dataExportUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(json => this.downloadCsv(json.data || [], 'recipient_payment_tasks.csv'))
                    .catch(() => this.toast(false, 'Failed to export recipient payment tasks.'));
                return;
            }
            window.location.href = `${this.config.dataExportUrl}?${params.toString()}`;
        },

        exportSessions(format) {
            const params = this.sessionQueryParams(this.sessionMeta.current_page || 1);
            params.set('format', format);
            if (format === 'csv') {
                fetch(`${this.config.sessionExportUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(json => this.downloadCsv(json.data || [], 'recipient_payment_sessions.csv'))
                    .catch(() => this.toast(false, 'Failed to export sessions.'));
                return;
            }
            window.location.href = `${this.config.sessionExportUrl}?${params.toString()}`;
        },

        printWallets() {
            const params = this.walletQueryParams(this.walletMeta.current_page || 1);
            params.set('format', 'json');
            fetch(`${this.config.walletExportUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(json => this.openPrintWindow(json.data || [], 'Payment Wallets'))
                .catch(() => this.toast(false, 'Failed to print wallets.'));
        },

        printTasks() {
            const params = this.taskQueryParams(this.meta.current_page || 1);
            params.set('format', 'json');
            fetch(`${this.config.dataExportUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(json => this.openPrintWindow(json.data || [], 'Recipient Payment Tasks'))
                .catch(() => this.toast(false, 'Failed to print recipient payment tasks.'));
        },

        printSessions() {
            const params = this.sessionQueryParams(this.sessionMeta.current_page || 1);
            params.set('format', 'json');
            fetch(`${this.config.sessionExportUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(json => this.openPrintWindow(json.data || [], 'Payment Sessions'))
                .catch(() => this.toast(false, 'Failed to print sessions.'));
        },

        downloadCsv(rows, filename) {
            if (!rows.length) {
                this.toast(false, 'There is no wallet data to export.');
                return;
            }
            const headers = Object.keys(rows[0]);
            const escapeCell = value => `"${String(value ?? '').replace(/"/g, '""')}"`;
            const csv = [headers.map(escapeCell).join(',')]
                .concat(rows.map(row => headers.map(header => escapeCell(row[header])).join(',')))
                .join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        },

        openPrintWindow(rows, title) {
            if (!rows.length) {
                this.toast(false, 'There is no wallet data to print.');
                return;
            }
            const headers = Object.keys(rows[0]);
            const html = `<!doctype html><html><head><title>${title}</title><style>body{font-family:Arial,sans-serif;padding:24px;color:#0f172a}table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #e2e8f0;padding:8px;text-align:left}th{background:#f8fafc;text-transform:uppercase;font-size:10px;color:#64748b}</style></head><body><h1>${title}</h1><table><thead><tr>${headers.map(header => `<th>${header}</th>`).join('')}</tr></thead><tbody>${rows.map(row => `<tr>${headers.map(header => `<td>${row[header] ?? ''}</td>`).join('')}</tr>`).join('')}</tbody></table></body></html>`;
            const popup = window.open('', '_blank');
            if (!popup) {
                this.toast(false, 'Allow popups to print wallet data.');
                return;
            }
            popup.document.write(html);
            popup.document.close();
            popup.focus();
            popup.print();
        },

        groupLabel(group) {
            if (group === 'mixed') return 'Mixed';
            return group === 'local_delivery' ? 'Local Delivery' : 'Warehouse Transfer';
        },

        methodLabel(method) {
            if (method === 'bus_handoff') return 'Bus station handoff';
            if (method === 'pickup') return 'Self pickup';
            return 'Direct delivery';
        },

        statusLabel(status) {
            return String(status || '').replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
        },

        statusClass(status) {
            if (status === 'paid') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (status === 'waived' || status === 'overridden') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
            if (status === 'disputed' || status === 'failed') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200';
        },

        callResultClass(result) {
            if (result === 'answered' || result === 'payment_promised') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (result === 'wrong_number') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            if (result === 'busy' || result === 'callback') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            if (result === 'no_answer') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
            return 'bg-slate-50 text-slate-500 ring-1 ring-slate-200';
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
            if (label === 'Paid') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (label === 'Waived' || label === 'Override') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
            if (label === 'No fee set') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200';
        },

        walletLabel(wallet) {
            return `${wallet.name} - ${wallet.phone_number}`;
        },

        activeWallets() {
            return this.wallets.filter(wallet => {
                if (!wallet.is_active) return false;
                if (this.canManageWallets) return true;
                const assignedUsers = wallet.assigned_users || [];
                return assignedUsers.some(user => Number(user.id) === Number(this.currentUserId));
            });
        },

        warehouseName(warehouseId) {
            if (!warehouseId) return 'All warehouses';
            return this.warehouses.find(warehouse => Number(warehouse.id) === Number(warehouseId))?.name || 'Warehouse';
        },

        assignedAgentNames(wallet) {
            return (wallet.assigned_users || []).map(user => user.name).join(', ') || 'No assigned agents';
        },

        selectedWalletAgentText() {
            const agents = this.selectedWalletAgents();
            if (agents.length === 0) return 'Select agents...';
            if (agents.length <= 2) return agents.map(agent => agent.name).join(', ');
            return `${agents.length} agents selected`;
        },

        selectedWalletAgents() {
            const selected = this.walletForm.user_ids.map(id => Number(id));
            return this.workers.filter(worker => selected.includes(Number(worker.id)));
        },

        filteredWalletAgents() {
            const search = this.walletAgentSearch.trim().toLowerCase();
            if (!search) return this.workers;

            return this.workers.filter(worker => String(worker.name || '').toLowerCase().includes(search));
        },

        isWalletAgentSelected(workerId) {
            return this.walletForm.user_ids.map(id => Number(id)).includes(Number(workerId));
        },

        toggleWalletAgent(workerId) {
            const id = Number(workerId);
            const selected = this.walletForm.user_ids.map(value => Number(value));
            this.walletForm.user_ids = selected.includes(id)
                ? selected.filter(value => value !== id)
                : [...selected, id];
        },

        removeWalletAgent(workerId) {
            const id = Number(workerId);
            this.walletForm.user_ids = this.walletForm.user_ids
                .map(value => Number(value))
                .filter(value => value !== id);
        },

        formatMoney(value) {
            return Number(value || 0).toFixed(2);
        },

        formatDateTime(value) {
            if (!value) return '-';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
            });
        },

        todayString() {
            const date = new Date();
            const pad = value => String(value).padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
        },

        toast(success, message) {
            this.notice = { success, message };
            if (success) setTimeout(() => { this.notice.message = ''; }, 4000);
        },
    };
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make($layoutName, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/kninson/Projects/parcelman/parcelman-backend/resources/views/shared/recipient-payments/index.blade.php ENDPATH**/ ?>
