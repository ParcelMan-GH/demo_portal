@extends('web.layouts.portal')

@section('title', 'View Invoice')

@section('content')
<main class="mx-auto min-h-screen w-full max-w-6xl px-6 py-10" x-data="vendorInvoiceShowPage()" data-invoice-id="{{ $invoiceId }}">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-orange-200">Vendor Portal</p>
            <h1 class="mt-1 text-3xl font-extrabold text-white">Invoice Details</h1>
            <p class="mt-2 text-sm text-slate-300" x-show="invoice" x-cloak>
                <span x-text="invoice?.invoice_number"></span>
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('web.vendor.invoices.index') }}" class="rounded-xl border border-slate-200/20 bg-slate-900/70 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">
                Back to Invoices
            </a>
            <a :href="invoice?.shipment_id ? `/vendor/shipments/${invoice.shipment_id}` : '#'" x-show="invoice?.shipment_id" x-cloak
               class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-2 text-sm font-semibold text-emerald-100 hover:bg-emerald-500/25">
                View Shipment
            </a>
            <button x-show="canRespond" type="button" @click="acceptInvoice()" x-cloak
                    class="rounded-xl border border-emerald-300/30 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-100 hover:bg-emerald-500/20">
                Accept
            </button>
            <button x-show="canRespond" type="button" @click="rejectInvoice()" x-cloak
                    class="rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-2 text-sm font-semibold text-rose-100 hover:bg-rose-500/20">
                Reject
            </button>
        </div>
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

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 backdrop-blur-xl" x-show="!loading && invoice" x-cloak>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <div class="text-xs uppercase tracking-[0.12em] text-slate-400">Status</div>
                <div class="mt-1 text-sm font-semibold text-white" x-text="statusLabel(invoice.status)"></div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-[0.12em] text-slate-400">Shipment</div>
                <div class="mt-1 text-sm font-semibold text-white" x-text="invoice.shipment_number || '-'"></div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-[0.12em] text-slate-400">Total</div>
                <div class="mt-1 text-sm font-semibold text-white" x-text="formatMoney(invoice.total_amount, invoice.currency)"></div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-[0.12em] text-slate-400">Sent</div>
                <div class="mt-1 text-sm text-slate-200" x-text="formatDateTime(invoice.sent_at)"></div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-[0.12em] text-slate-400">Accepted</div>
                <div class="mt-1 text-sm text-slate-200" x-text="formatDateTime(invoice.accepted_at)"></div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-[0.12em] text-slate-400">Rejected</div>
                <div class="mt-1 text-sm text-slate-200" x-text="formatDateTime(invoice.rejected_at)"></div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200/10 bg-slate-950/40 p-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-200">Fee Breakdown</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-400">Pickup Fee</dt>
                        <dd class="font-semibold text-white" x-text="formatMoney(invoice.pickup_fee, invoice.currency)"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-400">Transport Fee</dt>
                        <dd class="font-semibold text-white" x-text="formatMoney(invoice.transport_fee, invoice.currency)"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-400">Handling Fee</dt>
                        <dd class="font-semibold text-white" x-text="formatMoney(invoice.handling_fee, invoice.currency)"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-400">Other Fee</dt>
                        <dd class="font-semibold text-white" x-text="formatMoney(invoice.other_fee, invoice.currency)"></dd>
                    </div>
                    <div class="border-t border-slate-200/10 pt-2 flex items-center justify-between gap-4">
                        <dt class="text-slate-300 font-semibold">Total</dt>
                        <dd class="font-bold text-white" x-text="formatMoney(invoice.total_amount, invoice.currency)"></dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200/10 bg-slate-950/40 p-4">
                <h2 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-200">Notes</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div>
                        <div class="text-slate-400">Admin Notes</div>
                        <div class="text-slate-100" x-text="invoice.notes || '-'"></div>
                    </div>
                    <div>
                        <div class="text-slate-400">Vendor Notes</div>
                        <div class="text-slate-100" x-text="invoice.vendor_notes || '-'"></div>
                    </div>
                    <div>
                        <div class="text-slate-400">Rejection Reason</div>
                        <div class="text-slate-100" x-text="invoice.rejection_reason || '-'"></div>
                    </div>
                    <div>
                        <div class="text-slate-400">Cancel Reason</div>
                        <div class="text-slate-100" x-text="invoice.cancel_reason || '-'"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-slate-200/15 bg-slate-900/75 p-6 text-sm text-slate-300 backdrop-blur-xl"
             x-show="!loading && !invoice" x-cloak>
        Invoice details could not be loaded.
    </section>
</main>
@endsection
