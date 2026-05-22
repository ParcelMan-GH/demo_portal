<?php $__env->startSection('title', 'Recipient Payment Reports'); ?>
<?php $__env->startSection('breadcrumb-parent', 'Finance'); ?>
<?php $__env->startSection('breadcrumb-current', 'Recipient Payment Reports'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $reportConfig = [
        'indexUrl' => route($routePrefix . '.index'),
        'dataUrl' => route($routePrefix . '.reports.data'),
        'exportUrl' => route($routePrefix . '.reports.export'),
    ];
?>

<div class="space-y-6"
     data-config='<?php echo json_encode($reportConfig, 15, 512) ?>'
     data-workers='<?php echo json_encode($workers, 15, 512) ?>'
     data-wallets='<?php echo json_encode($wallets, 15, 512) ?>'
     data-warehouses='<?php echo json_encode($warehouses, 15, 512) ?>'
     data-agent-only='<?php echo json_encode($isAgentOnly ?? false, 15, 512) ?>'
     data-can-reconcile='<?php echo json_encode($canReconcile ?? false, 15, 512) ?>'
     x-data="recipientPaymentReportsPage()"
     x-init="init()">
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <a :href="config.indexUrl" class="group flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-600">
                        <svg class="h-5 w-5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900">Payment Reports</h2>
                        <p class="text-sm text-slate-500">Review recorded payments, call outcomes, wallets, and reconciliation details.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="search" @input.debounce.500ms="loadData(1)" placeholder="Search" class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''" :aria-expanded="showFilters.toString()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/>
                        </svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                        <svg class="h-4 w-4 transition-transform" :class="showFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                            </svg>
                            View
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <template x-for="col in availableColumns()" :key="col.key">
                                <button type="button" @click="visibleColumns[col.key] = !visibleColumns[col.key]" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <span x-text="col.label"></span>
                                    <svg x-show="visibleColumns[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display:none">
                            <button type="button" @click="exportReport('excel'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Excel</button>
                            <button type="button" @click="exportReport('pdf'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">PDF</button>
                            <button type="button" @click="exportReport('csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                            <button type="button" @click="printReport(); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="rp-report-filters mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/60 p-3 sm:p-4" style="display:none">
            <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Record Created Date</label>
                    <div class="relative">
                        <input type="text" x-ref="createdDateRange" placeholder="Select date range" class="rp-report-date-control w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm" readonly>
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Received Date</label>
                    <div class="relative">
                        <input type="text" x-ref="paidDateRange" placeholder="Select date range" class="rp-report-date-control w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm" readonly>
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Call Made Date</label>
                    <div class="relative">
                        <input type="text" x-ref="callDateRange" placeholder="Select date range" class="rp-report-date-control w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm" readonly>
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M4 11h16M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div x-show="warehouses.length > 1">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Warehouse</label>
                    <select x-model="warehouseId" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">All warehouses</option>
                        <template x-for="warehouse in warehouses" :key="warehouse.id"><option :value="warehouse.id" x-text="warehouse.name"></option></template>
                    </select>
                </div>
                <div x-show="canReconcile">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Agent</label>
                    <select x-model="agentId" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">All agents</option>
                        <template x-for="worker in workers" :key="worker.id"><option :value="worker.id" x-text="worker.name"></option></template>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Wallet</label>
                    <select x-model="walletId" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">All wallets</option>
                        <template x-for="wallet in wallets" :key="wallet.id"><option :value="wallet.id" x-text="walletLabel(wallet)"></option></template>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Group</label>
                    <select x-model="group" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">All groups</option><option value="local_delivery">Local Delivery</option><option value="warehouse_transfer">Warehouse Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Payment Status</label>
                    <select x-model="paymentStatus" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">All payment statuses</option><option value="paid">Paid</option><option value="due">Due</option><option value="waived">Waived</option><option value="overridden">Override</option><option value="no_fee">No fee set</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Call Result</label>
                    <select x-model="callResult" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">All call results</option><option value="not_called">Not called</option><option value="answered">Answered</option><option value="no_answer">No answer</option><option value="busy">Busy</option><option value="wrong_number">Wrong number</option><option value="callback">Call back</option><option value="payment_promised">Pay later</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Recipient Phone</label>
                    <input type="text" x-model="recipientPhone" placeholder="Recipient phone" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Delivery Method</label>
                    <select x-model="deliveryMethod" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                        <option value="">All delivery methods</option><option value="direct_delivery">Direct delivery</option><option value="bus_handoff">Bus station handoff</option><option value="pickup">Self pickup</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Delivery Fee Range</label>
                    <div class="flex overflow-hidden rounded-xl border-2 border-slate-200 bg-white transition focus-within:border-orange-400 focus-within:ring-4 focus-within:ring-orange-100">
                    <div class="flex min-w-0 flex-1 items-center">
                        <span class="pl-3 text-xs font-black uppercase tracking-wide text-slate-400">Min</span>
                        <input type="number" min="0" step="0.01" x-model="amountMin" placeholder="0.00" class="min-w-0 flex-1 border-0 bg-transparent px-2 py-3 text-base font-semibold text-slate-900 outline-none sm:text-sm">
                    </div>
                    <div class="w-px bg-slate-200"></div>
                    <div class="flex min-w-0 flex-1 items-center">
                        <span class="pl-3 text-xs font-black uppercase tracking-wide text-slate-400">Max</span>
                        <input type="number" min="0" step="0.01" x-model="amountMax" placeholder="0.00" class="min-w-0 flex-1 border-0 bg-transparent px-2 py-3 text-base font-semibold text-slate-900 outline-none sm:text-sm">
                    </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <button type="button" @click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                <button type="button" @click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                <button type="button" @click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
            </div>
            </div>

            <div class="relative rounded-xl border border-slate-200/70">
                <div x-show="loading" x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]" style="display:none"></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1600px] divide-y divide-slate-200/60 text-xs">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th colspan="22" class="bg-slate-50/90 px-4 py-3 text-left">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-500">Report totals</p>
                                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Recipients</span>
                                                <span class="font-extrabold text-slate-900" x-text="summary.recipients || 0"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Packages</span>
                                                <span class="font-extrabold text-slate-900" x-text="summary.packages || 0"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Paid</span>
                                                <span class="font-extrabold text-emerald-700" x-text="summary.paid || 0"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Due</span>
                                                <span class="font-extrabold text-amber-700" x-text="summary.due || 0"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Paid</span>
                                                <span class="font-extrabold text-slate-900">GHS <span x-text="formatMoney(summary.total_paid || 0)"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                            <tr>
                                <template x-for="col in availableColumns()" :key="col.key">
                                    <th x-show="visibleColumns[col.key]" @click="sort(col.key)" class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500" :class="col.sortable ? 'cursor-pointer' : ''">
                                        <div class="flex items-center gap-1">
                                            <span x-text="col.label"></span>
                                            <svg x-show="col.sortable" class="h-2.5 w-2.5" :class="sortBy === col.key ? 'text-slate-700' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/>
                                            </svg>
                                        </div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-if="!loading && rows.length === 0">
                                <tr><td colspan="22" class="px-4 py-14 text-center text-slate-400">No report records match the current filters.</td></tr>
                            </template>
                            <template x-for="row in rows" :key="row.id">
                                <tr class="transition hover:bg-orange-50/20">
                                    <td x-show="visibleColumns.created_at" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="formatDateTime(row.created_at)"></td>
                                    <td x-show="visibleColumns.paid_at" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="formatDateTime(row.paid_at)"></td>
                                    <td x-show="visibleColumns.assigned_to" class="whitespace-nowrap px-4 py-2.5 font-semibold text-slate-900" x-text="row.assigned_to || '-'"></td>
                                    <td x-show="visibleColumns.warehouse" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="row.warehouse || '-'"></td>
                                    <td x-show="visibleColumns.wallet" class="whitespace-nowrap px-4 py-2.5 text-slate-600"><span x-text="row.wallet || '-'"></span><p class="text-[10px] text-slate-400" x-text="row.wallet_phone || ''"></p></td>
                                    <td x-show="visibleColumns.recipient" class="px-4 py-2.5"><p class="font-semibold text-slate-900" x-text="row.recipient_name || '-'"></p><p class="text-[11px] text-slate-500" x-text="row.recipient_phone || '-'"></p></td>
                                    <td x-show="visibleColumns.location" class="px-4 py-2.5 text-slate-600" x-text="row.delivery_town || '-'"></td>
                                    <td x-show="visibleColumns.package" class="px-4 py-2.5"><p class="font-semibold text-slate-900" x-text="row.description || '-'"></p><p class="font-mono text-[11px] text-slate-500" x-text="row.tracking_code || '-'"></p></td>
                                    <td x-show="visibleColumns.quantity" class="whitespace-nowrap px-4 py-2.5 text-center font-bold text-slate-700" x-text="row.quantity || 1"></td>
                                    <td x-show="visibleColumns.group" class="whitespace-nowrap px-4 py-2.5"><span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="row.payment_group === 'local_delivery' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-orange-50 text-orange-700 ring-1 ring-orange-200'" x-text="row.payment_group_label"></span></td>
                                    <td x-show="visibleColumns.delivery_method" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="row.delivery_method_label"></td>
                                    <td x-show="visibleColumns.delivery_fee" class="whitespace-nowrap px-4 py-2.5 font-bold text-slate-900" x-text="row.fee_amount === null ? 'No fee set' : 'GHS ' + formatMoney(row.fee_amount)"></td>
                                    <td x-show="visibleColumns.payment_status" class="whitespace-nowrap px-4 py-2.5"><span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="paymentStatusClass(row.payment_status_label)" x-text="row.payment_status_label"></span></td>
                                    <td x-show="visibleColumns.call_result" class="whitespace-nowrap px-4 py-2.5"><p class="font-semibold text-slate-800" x-text="row.call_result_label"></p><p x-show="row.last_call_at" class="mt-1 whitespace-nowrap text-[10px] text-slate-400" x-text="formatDateTime(row.last_call_at)"></p></td>
                                    <td x-show="visibleColumns.reference" class="whitespace-nowrap px-4 py-2.5 text-slate-600" x-text="row.payment_reference || '-'"></td>
                                    <td x-show="visibleColumns.receipt" class="whitespace-nowrap px-4 py-2.5">
                                        <a x-show="row.receipt_url" :href="row.receipt_url" target="_blank" class="inline-flex rounded-full bg-orange-50 px-2.5 py-1 text-[10px] font-bold text-orange-700 ring-1 ring-orange-200">View</a>
                                        <span x-show="!row.receipt_url" class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600 ring-1 ring-slate-200">None</span>
                                    </td>
                                    <td x-show="visibleColumns.session" class="whitespace-nowrap px-4 py-2.5 text-slate-600"><span x-text="row.session || '-'"></span><p class="text-[10px] text-slate-400" x-text="row.session_status || ''"></p></td>
                                    <td x-show="visibleColumns.notes" class="px-4 py-2.5 text-slate-500" x-text="row.notes || '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50/90">
                            <tr>
                                <td colspan="22" class="px-4 py-3">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-500">Report totals</p>
                                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Recipients</span>
                                                <span class="font-extrabold text-slate-900" x-text="summary.recipients || 0"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Packages</span>
                                                <span class="font-extrabold text-slate-900" x-text="summary.packages || 0"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Paid</span>
                                                <span class="font-extrabold text-emerald-700" x-text="summary.paid || 0"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Due</span>
                                                <span class="font-extrabold text-amber-700" x-text="summary.due || 0"></span>
                                            </div>
                                            <div class="inline-flex items-center gap-2">
                                                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Paid</span>
                                                <span class="font-extrabold text-slate-900">GHS <span x-text="formatMoney(summary.total_paid || 0)"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="border-t border-slate-200/50 bg-slate-50/30 px-4 py-2.5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <p class="text-xs text-slate-600">Showing <span x-text="meta.from"></span> to <span x-text="meta.to"></span> of <span x-text="meta.total"></span> results</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @click="open = !open" class="inline-flex min-w-[60px] items-center justify-between gap-1.5 rounded-lg border border-slate-200/70 bg-white px-2.5 py-1 text-xs font-medium text-slate-700">
                                        <span x-text="perPage"></span>
                                        <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full right-0 z-[9999] mb-1 w-16 rounded-lg border border-slate-200 bg-white p-1 shadow-lg" style="display:none">
                                        <template x-for="size in [10, 25, 50, 100]" :key="size">
                                            <button type="button" @click="perPage = size; loadData(1); open = false" class="w-full rounded px-2 py-1 text-center text-xs font-medium text-slate-700 hover:bg-slate-100" :class="perPage === size ? 'bg-slate-100' : ''" x-text="size"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs font-medium text-slate-600">Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span></span>
                            <div class="flex space-x-1">
                                <button type="button" @click="goPage(1)" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white/50 text-slate-600"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg></button>
                                <button type="button" @click="goPage(meta.current_page - 1)" :disabled="meta.current_page <= 1" :class="meta.current_page <= 1 ? 'cursor-not-allowed opacity-50' : 'hover:bg-white'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white/50 text-slate-600"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                                <button type="button" @click="goPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white/50 text-slate-600"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                                <button type="button" @click="goPage(meta.last_page)" :disabled="meta.current_page >= meta.last_page" :class="meta.current_page >= meta.last_page ? 'cursor-not-allowed opacity-50' : 'hover:bg-white'" class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white/50 text-slate-600"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function recipientPaymentReportsPage() {
    return {
        config: {},
        workers: [],
        wallets: [],
        warehouses: [],
        agentOnly: false,
        canReconcile: false,
        rows: [],
        loading: false,
        showFilters: false,
        meta: { total: 0, from: 0, to: 0, current_page: 1, last_page: 1 },
        summary: { recipients: 0, packages: 0, paid: 0, due: 0, total_paid: 0 },
        search: '',
        createdDateFrom: '',
        createdDateTo: '',
        paidDateFrom: '',
        paidDateTo: '',
        callDateFrom: '',
        callDateTo: '',
        warehouseId: '',
        agentId: '',
        walletId: '',
        group: '',
        paymentStatus: '',
        callResult: '',
        recipientPhone: '',
        deliveryMethod: '',
        amountMin: '',
        amountMax: '',
        appliedFilters: {},
        dateRangePickers: {},
        perPage: 25,
        sortBy: 'created_at',
        sortDirection: 'desc',
        columns: [
            { key: 'created_at', label: 'Task Date', sortable: true },
            { key: 'paid_at', label: 'Paid Date', sortable: true },
            { key: 'assigned_to', label: 'Agent', sortable: false },
            { key: 'warehouse', label: 'Warehouse', sortable: false },
            { key: 'wallet', label: 'Wallet', sortable: false },
            { key: 'recipient', label: 'Recipient', sortable: true },
            { key: 'location', label: 'Location', sortable: true },
            { key: 'package', label: 'Package', sortable: false },
            { key: 'quantity', label: 'Qty', sortable: false },
            { key: 'group', label: 'Group', sortable: true },
            { key: 'delivery_method', label: 'Method', sortable: false },
            { key: 'delivery_fee', label: 'Delivery Fee', sortable: true },
            { key: 'payment_status', label: 'Payment Status', sortable: true },
            { key: 'call_result', label: 'Call Result', sortable: false },
            { key: 'reference', label: 'Reference', sortable: false },
            { key: 'receipt', label: 'Receipt', sortable: false },
            { key: 'session', label: 'Session', sortable: false },
            { key: 'notes', label: 'Notes', sortable: false },
        ],
        visibleColumns: {
            created_at: true,
            paid_at: true,
            assigned_to: true,
            warehouse: true,
            wallet: true,
            recipient: true,
            location: true,
            package: true,
            quantity: true,
            group: true,
            delivery_method: true,
            delivery_fee: true,
            payment_status: true,
            call_result: true,
            reference: true,
            receipt: true,
            session: false,
            notes: false,
        },
        init() {
            this.config = JSON.parse(this.$el.dataset.config || '{}');
            this.workers = JSON.parse(this.$el.dataset.workers || '[]');
            this.wallets = JSON.parse(this.$el.dataset.wallets || '[]');
            this.warehouses = JSON.parse(this.$el.dataset.warehouses || '[]');
            this.agentOnly = JSON.parse(this.$el.dataset.agentOnly || 'false');
            this.canReconcile = JSON.parse(this.$el.dataset.canReconcile || 'false');
            this.applyContextualColumns();
            this.createdDateFrom = this.todayString();
            this.createdDateTo = this.todayString();
            this.syncAppliedFilters();
            this.$nextTick(() => this.initDateRange());
            this.loadData(1);
        },
        availableColumns() {
            return this.columns.filter((column) => this.isColumnAvailable(column.key));
        },
        isColumnAvailable(key) {
            if (key === 'assigned_to' && this.agentOnly) {
                return false;
            }

            if (key === 'warehouse' && this.warehouses.length <= 1) {
                return false;
            }

            return true;
        },
        applyContextualColumns() {
            Object.keys(this.visibleColumns).forEach((key) => {
                if (!this.isColumnAvailable(key)) {
                    this.visibleColumns[key] = false;
                }
            });
        },
        syncAppliedFilters() {
            this.appliedFilters = {
                createdDateFrom: this.createdDateFrom,
                createdDateTo: this.createdDateTo,
                paidDateFrom: this.paidDateFrom,
                paidDateTo: this.paidDateTo,
                callDateFrom: this.callDateFrom,
                callDateTo: this.callDateTo,
                warehouseId: this.warehouseId,
                agentId: this.agentId,
                walletId: this.walletId,
                group: this.group,
                paymentStatus: this.paymentStatus,
                callResult: this.callResult,
                recipientPhone: this.recipientPhone,
                deliveryMethod: this.deliveryMethod,
                amountMin: this.amountMin,
                amountMax: this.amountMax,
            };
        },
        applyFilters() {
            this.syncAppliedFilters();
            this.loadData(1);
        },
        clearFilters() {
            this.createdDateFrom = this.todayString();
            this.createdDateTo = this.todayString();
            this.paidDateFrom = '';
            this.paidDateTo = '';
            this.callDateFrom = '';
            this.callDateTo = '';
            this.warehouseId = '';
            this.agentId = '';
            this.walletId = '';
            this.group = '';
            this.paymentStatus = '';
            this.callResult = '';
            this.recipientPhone = '';
            this.deliveryMethod = '';
            this.amountMin = '';
            this.amountMax = '';
            if (this.$refs.createdDateRange) this.$refs.createdDateRange.value = `${this.createdDateFrom} - ${this.createdDateTo}`;
            if (this.$refs.paidDateRange) this.$refs.paidDateRange.value = '';
            if (this.$refs.callDateRange) this.$refs.callDateRange.value = '';
            this.syncAppliedFilters();
            this.loadData(1);
        },
        todayString() {
            const now = new Date();
            return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        },
        initDateRange() {
            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;
                this.setupReportDatePicker('created', this.$refs.createdDateRange, 'createdDateFrom', 'createdDateTo', true);
                this.setupReportDatePicker('paid', this.$refs.paidDateRange, 'paidDateFrom', 'paidDateTo', false);
                this.setupReportDatePicker('call', this.$refs.callDateRange, 'callDateFrom', 'callDateTo', false);
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
                .catch(() => {});
        },
        setupReportDatePicker(key, el, fromProp, toProp, defaultToday = false) {
            if (!el || !window.$ || !window.moment || !window.$.fn.daterangepicker) return;
            const $input = window.$(el);
            if ($input.data('daterangepicker')) return;

            const start = window.moment(this[fromProp] || this.todayString(), 'YYYY-MM-DD');
            const end = window.moment(this[toProp] || this.todayString(), 'YYYY-MM-DD');

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

            if (defaultToday) {
                $input.val(`${this[fromProp]} - ${this[toProp]}`);
            }

            $input.on('apply.daterangepicker', (ev, picker) => {
                this[fromProp] = picker.startDate.format('YYYY-MM-DD');
                this[toProp] = picker.endDate.format('YYYY-MM-DD');
                $input.val(`${this[fromProp]} - ${this[toProp]}`);
            });

            $input.on('cancel.daterangepicker', () => {
                this[fromProp] = '';
                this[toProp] = '';
                $input.val('');
            });

            this.dateRangePickers[key] = $input.data('daterangepicker');
        },
        queryParams(page = 1) {
            const params = new URLSearchParams({
                page,
                per_page: this.perPage,
                sort: this.sortBy,
                direction: this.sortDirection,
            });
            const filters = this.appliedFilters || {};
            if (filters.createdDateFrom) params.set('created_date_from', filters.createdDateFrom);
            if (filters.createdDateTo) params.set('created_date_to', filters.createdDateTo);
            if (filters.paidDateFrom) params.set('paid_date_from', filters.paidDateFrom);
            if (filters.paidDateTo) params.set('paid_date_to', filters.paidDateTo);
            if (filters.callDateFrom) params.set('call_date_from', filters.callDateFrom);
            if (filters.callDateTo) params.set('call_date_to', filters.callDateTo);
            if (this.search) params.set('search', this.search);
            if (filters.warehouseId) params.set('warehouse_id', filters.warehouseId);
            if (filters.agentId) params.set('agent_id', filters.agentId);
            if (filters.walletId) params.set('wallet_id', filters.walletId);
            if (filters.group) params.set('group', filters.group);
            if (filters.paymentStatus) params.set('payment_status', filters.paymentStatus);
            if (filters.callResult) params.set('call_result', filters.callResult);
            if (filters.recipientPhone) params.set('recipient_phone', filters.recipientPhone);
            if (filters.deliveryMethod) params.set('delivery_method', filters.deliveryMethod);
            if (filters.amountMin) params.set('amount_min', filters.amountMin);
            if (filters.amountMax) params.set('amount_max', filters.amountMax);
            return params;
        },
        async loadData(page = 1) {
            this.loading = true;
            try {
                const res = await fetch(`${this.config.dataUrl}?${this.queryParams(page).toString()}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.rows = json.data || [];
                this.meta = json.meta || this.meta;
                this.summary = json.summary || this.summary;
            } finally {
                this.loading = false;
            }
        },
        sort(key) {
            const col = this.columns.find(column => column.key === key);
            if (!col?.sortable) return;
            const sortMap = {
                created_at: 'created_at',
                paid_at: 'paid_at',
                recipient: 'recipient_name',
                location: 'delivery_town',
                group: 'payment_group',
                delivery_fee: 'negotiated_amount',
                payment_status: 'status',
            };
            const mapped = sortMap[key] || key;
            if (this.sortBy === mapped) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = mapped;
                this.sortDirection = 'asc';
            }
            this.loadData(1);
        },
        goPage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.loadData(page);
        },
        exportReport(format) {
            if (format === 'csv') {
                fetch(`${this.config.exportUrl}?${this.queryParams(1).toString()}&format=json`, { headers: { 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(json => this.downloadCsv(json.data || [], 'recipient_payment_report.csv'));
                return;
            }
            window.location.href = `${this.config.exportUrl}?${this.queryParams(1).toString()}&format=${format}`;
        },
        printReport() {
            fetch(`${this.config.exportUrl}?${this.queryParams(1).toString()}&format=json`, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(json => {
                    const rows = json.data || [];
                    const popup = window.open('', '_blank');
                    if (!popup) return;
                    popup.document.write(`<html><head><title>Recipient Payment Report</title><style>body{font-family:Arial,sans-serif;padding:24px}table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f5f5}</style></head><body><h1>Recipient Payment Report</h1>${this.rowsToTable(rows)}</body></html>`);
                    popup.document.close();
                    popup.print();
                });
        },
        rowsToTable(rows) {
            if (!rows.length) return '<p>No records</p>';
            const headers = Object.keys(rows[0]);
            return `<table><thead><tr>${headers.map(header => `<th>${header}</th>`).join('')}</tr></thead><tbody>${rows.map(row => `<tr>${headers.map(header => `<td>${row[header] ?? ''}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
        },
        downloadCsv(rows, filename) {
            if (!rows.length) return;
            const headers = Object.keys(rows[0]);
            const escape = value => `"${String(value ?? '').replaceAll('"', '""')}"`;
            const csv = [headers.map(escape).join(','), ...rows.map(row => headers.map(header => escape(row[header])).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        },
        formatMoney(value) {
            return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatDateTime(value) {
            if (!value) return '-';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString([], { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
        },
        walletLabel(wallet) {
            return `${wallet.name} - ${wallet.phone_number}`;
        },
        paymentStatusClass(label) {
            if (label === 'Paid') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (label === 'Waived' || label === 'Override') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
            if (label === 'No fee set') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            return 'bg-orange-50 text-orange-700 ring-1 ring-orange-200';
        },
        callResultClass(result) {
            if (result === 'answered' || result === 'payment_promised') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (result === 'wrong_number') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            if (result === 'busy' || result === 'callback') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            if (result === 'no_answer') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
            return 'bg-slate-50 text-slate-500 ring-1 ring-slate-200';
        },
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make($layoutName, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
