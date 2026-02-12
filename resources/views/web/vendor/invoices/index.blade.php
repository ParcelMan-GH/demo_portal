@extends('web.layouts.portal')

@section('title', 'Vendor Invoices')

@section('content')
<main class="mx-auto min-h-screen w-full max-w-7xl px-6 py-10" x-data="vendorInvoicesListPage()">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-200">Vendor Portal</p>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Invoices</h1>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('web.vendor.home') }}" class="rounded-xl border border-slate-200/20 bg-slate-900/70 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">
                Home
            </a>
            <a href="{{ route('web.vendor.shipments.index') }}" class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-100 hover:bg-emerald-500/25">
                Shipments
            </a>
        </div>
    </div>

    <div class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl">
        <form class="grid gap-4 md:grid-cols-2 lg:grid-cols-4" @submit.prevent="applyFilters()">
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">Shipment</label>
                <select x-model="filters.shipment_id" @change="onShipmentChange()"
                        class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <option value="" class="text-slate-900">All shipments</option>
                    <template x-for="shipment in shipments" :key="shipment.id">
                        <option :value="shipment.id" x-text="shipment.shipment_number" class="text-slate-900"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">Invoice Number</label>
                <select x-model="filters.invoice_number"
                        class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <option value="" class="text-slate-900">All invoices</option>
                    <template x-for="invoice in invoiceOptions" :key="invoice.id">
                        <option :value="invoice.invoice_number" x-text="`${invoice.invoice_number} (${invoice.shipment_number})`" class="text-slate-900"></option>
                    </template>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">Search</label>
                <input x-model="filters.search" type="text" placeholder="Invoice #, shipment #, status, notes..."
                       class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-4 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">From Date</label>
                <input x-model="filters.from_date" type="date"
                       class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">To Date</label>
                <input x-model="filters.to_date" type="date"
                       class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">Status</label>
                <select x-model="filters.status" multiple
                        class="h-[120px] w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <template x-for="status in statuses" :key="status.value">
                        <option :value="status.value" x-text="status.label" class="text-slate-900"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">Limit</label>
                <select x-model.number="filters.limit"
                        class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <option value="10" class="text-slate-900">10</option>
                    <option value="15" class="text-slate-900">15</option>
                    <option value="25" class="text-slate-900">25</option>
                    <option value="50" class="text-slate-900">50</option>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">Sort By</label>
                <select x-model="filters.sort_by"
                        class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <template x-for="field in sortFields" :key="field.value">
                        <option :value="field.value" x-text="field.label" class="text-slate-900"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-300">Sort Order</label>
                <select x-model="filters.sort_order"
                        class="w-full rounded-xl border border-slate-300/20 bg-slate-950/70 px-3 py-2 text-sm text-white outline-none transition focus:border-orange-300/70 focus:ring-2 focus:ring-orange-400/35">
                    <option value="desc" class="text-slate-900">Newest First</option>
                    <option value="asc" class="text-slate-900">Oldest First</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-400">
                    Apply
                </button>
                <button type="button" @click="resetFilters()"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300/25 bg-slate-800/70 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-slate-700">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 space-y-3">
        <div x-show="alert" x-cloak class="rounded-xl border px-4 py-3 text-sm"
             :class="{
                'border-emerald-300/30 bg-emerald-400/10 text-emerald-100': alert?.type === 'success',
                'border-rose-300/30 bg-rose-500/10 text-rose-100': alert?.type === 'error'
             }">
            <span x-text="alert?.message"></span>
        </div>

        <div x-show="validationErrors.length" x-cloak class="rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
            <ul class="list-disc pl-5">
                <template x-for="err in validationErrors" :key="err">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>
    </div>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl">
        <div x-show="loading" class="py-10 text-center text-sm text-slate-300">Loading invoices...</div>

        <div x-show="!loading" x-cloak>
            <div class="overflow-auto rounded-2xl border border-slate-200/10">
                <table class="min-w-full divide-y divide-slate-200/10 text-sm">
                    <thead class="bg-slate-900/90">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-300">Invoice #</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-300">Shipment</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-300">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-300">Total</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-300">Created</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/10">
                        <template x-for="invoice in invoices" :key="invoice.id">
                            <tr class="bg-slate-950/40">
                                <td class="px-4 py-3 font-semibold text-white" x-text="invoice.invoice_number"></td>
                                <td class="px-4 py-3 text-slate-200" x-text="invoice.shipment_number || '-'"></td>
                                <td class="px-4 py-3 text-slate-200" x-text="statusLabel(invoice.status)"></td>
                                <td class="px-4 py-3 text-slate-200" x-text="formatMoney(invoice.total_amount, invoice.currency)"></td>
                                <td class="px-4 py-3 text-slate-200" x-text="formatDateTime(invoice.created_at)"></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a :href="`/vendor/invoices/${invoice.id}`"
                                           class="rounded-lg border border-slate-300/30 px-2.5 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800">View</a>
                                        <button x-show="invoice.status === 'sent'" type="button" @click="acceptInvoice(invoice)"
                                                class="rounded-lg border border-emerald-300/30 px-2.5 py-1 text-xs font-semibold text-emerald-100 hover:bg-emerald-500/15">
                                            Accept
                                        </button>
                                        <button x-show="invoice.status === 'sent'" type="button" @click="rejectInvoice(invoice)"
                                                class="rounded-lg border border-rose-300/30 px-2.5 py-1 text-xs font-semibold text-rose-100 hover:bg-rose-500/15">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="invoices.length === 0" class="mt-4 rounded-xl border border-slate-200/10 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-300">
                No invoices found for the selected filters.
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-300">
                <div>
                    Showing page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>,
                    total <span x-text="pagination.total"></span> invoices
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="previousPage()"
                            class="rounded-lg border border-slate-300/30 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800">
                        Previous
                    </button>
                    <button type="button" @click="nextPage()" :disabled="!pagination.has_more"
                            class="rounded-lg border border-slate-300/30 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
