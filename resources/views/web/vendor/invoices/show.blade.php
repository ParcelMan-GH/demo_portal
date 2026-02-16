@extends('web.layouts.vendor')

@section('title', 'Invoice Details')
@section('page-title', 'Invoice Details')

@section('content')
<div x-data="vendorInvoiceShowPage()" data-invoice-id="{{ $invoiceId }}">
    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <div class="text-center">
            <svg class="mx-auto h-6 w-6 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-3 text-sm text-slate-500">Loading invoice...</p>
        </div>
    </div>

    {{-- Alert --}}
    <div class="mb-4" x-show="alert" x-cloak>
        <div class="rounded-lg border px-4 py-3 text-sm"
             :class="{ 'border-green-200 bg-green-50 text-green-700': alert?.type === 'success', 'border-red-200 bg-red-50 text-red-700': alert?.type === 'error' }">
            <span x-text="alert?.message"></span>
        </div>
    </div>

    {{-- Validation Errors --}}
    <div class="mb-4" x-show="validationErrors.length" x-cloak>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                <template x-for="err in validationErrors" :key="err">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>
    </div>

    {{-- Invoice content --}}
    <div x-show="!loading && invoice" x-cloak class="space-y-6">
        {{-- Invoice header --}}
        <div class="vendor-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900" x-text="invoice.invoice_number"></h2>
                    <span class="vendor-badge mt-1 inline-block" :class="'vendor-badge-' + invoice.status" x-text="statusLabel(invoice.status)"></span>
                </div>
                <div class="flex flex-wrap gap-2" x-show="canRespond" x-cloak>
                    <button type="button" @click="acceptInvoice()"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                        Accept Invoice
                    </button>
                    <button type="button" @click="rejectInvoice()"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                        Reject Invoice
                    </button>
                </div>
            </div>
        </div>

        {{-- Invoice info grid --}}
        <div class="vendor-card p-5">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-400">Invoice Information</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <div class="text-xs font-medium text-slate-500">Shipment #</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="invoice.shipment_number || '-'"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Status</div>
                    <div class="mt-1">
                        <span class="vendor-badge" :class="'vendor-badge-' + invoice.status" x-text="statusLabel(invoice.status)"></span>
                    </div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Total Amount</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="formatMoney(invoice.total_amount, invoice.currency)"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Currency</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="invoice.currency || '-'"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Created</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="formatDateTime(invoice.created_at)"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Sent</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="formatDateTime(invoice.sent_at)"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Accepted</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="formatDateTime(invoice.accepted_at)"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Rejected</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="formatDateTime(invoice.rejected_at)"></div>
                </div>
            </div>
        </div>

        {{-- Fee breakdown --}}
        <div class="vendor-card p-5">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-400">Fee Breakdown</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-xs font-medium text-slate-500">Pickup Fee</dt>
                    <dd class="font-semibold text-slate-900" x-text="formatMoney(invoice.pickup_fee, invoice.currency)"></dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-xs font-medium text-slate-500">Transport Fee</dt>
                    <dd class="font-semibold text-slate-900" x-text="formatMoney(invoice.transport_fee, invoice.currency)"></dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-xs font-medium text-slate-500">Handling Fee</dt>
                    <dd class="font-semibold text-slate-900" x-text="formatMoney(invoice.handling_fee, invoice.currency)"></dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-xs font-medium text-slate-500">Other Fee</dt>
                    <dd class="font-semibold text-slate-900" x-text="formatMoney(invoice.other_fee, invoice.currency)"></dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3">
                    <dt class="text-sm font-semibold text-slate-700">Total</dt>
                    <dd class="text-base font-bold text-slate-900" x-text="formatMoney(invoice.total_amount, invoice.currency)"></dd>
                </div>
            </dl>
        </div>

        {{-- Notes --}}
        <div class="vendor-card p-5">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-400">Notes</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <div class="text-xs font-medium text-slate-500">Admin Notes</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="invoice.notes || '-'"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Vendor Notes</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="invoice.vendor_notes || '-'"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Rejection Reason</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="invoice.rejection_reason || '-'"></div>
                </div>
                <div>
                    <div class="text-xs font-medium text-slate-500">Cancel Reason</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="invoice.cancel_reason || '-'"></div>
                </div>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="flex flex-wrap items-center gap-3" x-show="canRespond" x-cloak>
            <button type="button" @click="acceptInvoice()"
                    class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                Accept Invoice
            </button>
            <button type="button" @click="rejectInvoice()"
                    class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                Reject Invoice
            </button>
        </div>

        {{-- Back link --}}
        <div>
            <a href="{{ route('web.vendor.invoices.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-slate-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Invoices
            </a>
        </div>
    </div>

    {{-- Not found state --}}
    <div x-show="!loading && !invoice" x-cloak class="vendor-card px-6 py-12 text-center">
        <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        <p class="mt-2 text-sm text-slate-500">Invoice details could not be loaded.</p>
        <a href="{{ route('web.vendor.invoices.index') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-slate-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Invoices
        </a>
    </div>
</div>
@endsection
