@extends('warehouse.layouts.app')

@section('title', 'Invoice - ' . $invoice->invoice_number)
@section('breadcrumb-parent', 'Invoices')
@section('page-title', $invoice->invoice_number)

@php
$invoiceConfig = [
    'invoice' => $invoice,
    'downloadEndpoint' => route('warehouse.invoices.download', $invoice),
    'paymentsDataEndpoint' => route('warehouse.invoices.payments.data', $invoice),
    'storePaymentEndpoint' => route('warehouse.invoices.payments.store', $invoice),
    'destroyPaymentEndpointTemplate' => route('warehouse.payments.destroy', ['payment' => '__PAYMENT__']),
    'downloadReceiptUrlTemplate' => route('warehouse.payments.download', ['payment' => '__PAYMENT__']),
    'printReceiptUrlTemplate' => route('warehouse.payments.print', ['payment' => '__PAYMENT__']),
    'sendEndpoint' => route('warehouse.invoices.send', $invoice),
    'cancelEndpoint' => route('warehouse.invoices.cancel', $invoice),
    'adminAcceptEndpoint' => route('warehouse.invoices.admin-accept', $invoice),
    'updateEndpoint' => route('warehouse.invoices.update', $invoice),
    'canManage' => $canManage,
    'isSuperAdmin' => auth('admin')->user()?->isSuperAdmin() ?? false,
];
@endphp

@section('content')
<div x-data="invoiceShow()" data-invoice-show-config="{{ json_encode($invoiceConfig) }}" class="space-y-6">

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-slate-800 via-slate-900 to-slate-950 rounded-3xl overflow-hidden shadow-2xl shadow-slate-900/30">
        <div class="relative">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)"/>
                </svg>
            </div>

            <div class="relative px-6 lg:px-8 py-6">
                <!-- Top Row -->
                <div class="flex items-center justify-between mb-6">
                    <button onclick="window.history.back()" class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium transition-all backdrop-blur-sm hover:shadow-md">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="text-xs">Back</span>
                    </button>

                    <div class="flex items-center gap-2">
                        <!-- Download PDF -->
                        <a href="{{ route('warehouse.invoices.download', $invoice) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 transition-all backdrop-blur-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download PDF
                        </a>
                        <!-- Print -->
                        <a href="{{ route('warehouse.invoices.print', $invoice) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 transition-all backdrop-blur-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print
                        </a>

                        @if($canManage)
                        @if($invoice->status === \App\Enums\InvoiceStatus::PENDING)
                        <button @@click="sendInvoice()" :disabled="actionLoading" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 text-xs font-semibold rounded-xl border border-blue-500/30 transition-all backdrop-blur-sm disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Send to Vendor
                        </button>
                        @endif
                        @if($invoice->status === \App\Enums\InvoiceStatus::SENT)
                        <button @@click="acceptInvoice()" :disabled="actionLoading" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 text-xs font-semibold rounded-xl border border-emerald-500/30 transition-all backdrop-blur-sm disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Accept Invoice
                        </button>
                        @endif
                        @if(in_array($invoice->status, [\App\Enums\InvoiceStatus::PENDING, \App\Enums\InvoiceStatus::SENT]))
                        <button @@click="cancelInvoice()" :disabled="actionLoading" class="inline-flex items-center gap-2 px-4 py-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 text-xs font-semibold rounded-xl border border-rose-500/30 transition-all backdrop-blur-sm disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancel
                        </button>
                        @endif
                        @endif
                    </div>
                </div>

                <!-- Main Row -->
                <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                    <!-- LEFT: Invoice Info -->
                    <div class="flex items-start gap-5 lg:flex-shrink-0">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 lg:w-24 lg:h-24 rounded-2xl bg-gradient-to-br from-orange-500 via-orange-600 to-red-600 flex items-center justify-center text-white shadow-xl shadow-orange-500/30 ring-4 ring-white/10">
                                <svg class="w-10 h-10 lg:w-12 lg:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-2 min-w-0">
                            <div>
                                <h1 class="text-xl lg:text-2xl font-bold text-white truncate">{{ $invoice->invoice_number }}</h1>
                                @if($invoice->shipment)
                                <p class="text-slate-400 text-sm mt-0.5">
                                    Shipment: <span class="text-blue-400">{{ $invoice->shipment->shipment_number }}</span>
                                </p>
                                @endif
                                @if($invoice->shipment?->vendor)
                                <p class="text-slate-500 text-xs mt-0.5 truncate">
                                    {{ $invoice->shipment->vendor->name }}
                                    @if($invoice->shipment->vendor->business_name)
                                        &mdash; {{ $invoice->shipment->vendor->business_name }}
                                    @endif
                                </p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                    @switch($invoice->status->value)
                                        @case('pending') bg-amber-400/20 text-amber-300 @break
                                        @case('sent') bg-blue-400/20 text-blue-300 @break
                                        @case('accepted') bg-emerald-400/20 text-emerald-300 @break
                                        @case('rejected') bg-rose-400/20 text-rose-300 @break
                                        @case('cancelled') bg-gray-400/20 text-gray-300 @break
                                    @endswitch
                                ">
                                    {{ $invoice->status->label() }}
                                </span>
                                <span class="text-slate-500 text-xs">Created {{ $invoice->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Summary Stats -->
                    <div class="lg:ml-auto">
                        <div class="flex flex-col gap-3">
                            <div class="grid grid-cols-3 gap-3 max-w-sm">
                                <div class="bg-white/5 rounded-xl p-3 border border-white/10">
                                    <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Total Amount</p>
                                    <p class="text-lg font-bold text-white mt-1">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->total_amount, 2) }}</p>
                                </div>
                                <div class="bg-white/5 rounded-xl p-3 border border-white/10">
                                    <p class="text-[10px] font-semibold text-emerald-400 uppercase tracking-wider">Total Paid</p>
                                    <p class="text-lg font-bold text-emerald-400 mt-1" x-text="'{{ $invoice->currency ?: 'GHS' }} ' + (paymentsData.summary?.total_paid || 0).toFixed(2)">{{ $invoice->currency ?: 'GHS' }} 0.00</p>
                                </div>
                                <div class="bg-white/5 rounded-xl p-3 border border-white/10">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider" :class="(paymentsData.summary?.balance_due || 0) > 0 ? 'text-rose-400' : 'text-emerald-400'">Balance Due</p>
                                    <p class="text-lg font-bold mt-1" :class="(paymentsData.summary?.balance_due || 0) > 0 ? 'text-rose-400' : 'text-emerald-400'" x-text="'{{ $invoice->currency ?: 'GHS' }} ' + (paymentsData.summary?.balance_due ?? {{ (float) $invoice->total_amount }}).toFixed(2)">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->total_amount, 2) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($invoice->sent_at)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-400/15 text-blue-300 border border-blue-400/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    Sent {{ $invoice->sent_at->format('M d, Y') }}
                                </span>
                                @endif
                                @if($invoice->accepted_at)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-400/15 text-emerald-300 border border-emerald-400/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Accepted {{ $invoice->accepted_at->format('M d, Y') }}
                                </span>
                                @endif
                                @if($invoice->rejected_at)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-400/15 text-rose-300 border border-rose-400/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Rejected {{ $invoice->rejected_at->format('M d, Y') }}
                                </span>
                                @endif
                                @if($invoice->cancelled_at)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-400/15 text-gray-400 border border-gray-400/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Cancelled {{ $invoice->cancelled_at->format('M d, Y') }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex min-h-[680px]">

        <!-- Sidebar Nav -->
        <aside class="w-52 flex-shrink-0 bg-white border-r border-slate-100 flex flex-col py-4 px-2.5">

            <p class="px-1.5 mb-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Invoice</p>

            <!-- Details -->
            <button @@click="activeTab = 'details'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'details' ? 'bg-orange-50 ring-1 ring-orange-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'details' ? 'bg-orange-500 shadow-sm shadow-orange-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'details' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'details' ? 'font-bold text-orange-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Details</span>
            </button>

            <!-- Fee Breakdown -->
            <button @@click="activeTab = 'fees'"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'fees' ? 'bg-sky-50 ring-1 ring-sky-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'fees' ? 'bg-sky-500 shadow-sm shadow-sky-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'fees' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'fees' ? 'font-bold text-sky-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Fee Breakdown</span>
            </button>

            <!-- Divider -->
            <div class="flex items-center gap-2 mt-3 mb-1.5 px-1">
                <div class="flex-1 h-px bg-slate-100"></div>
                <p class="text-[8px] font-bold text-slate-300 uppercase tracking-widest">Finance</p>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Payments -->
            <button @@click="activeTab = 'payments'; if (!paymentsLoaded) loadPayments()"
                class="group flex items-center gap-2 w-full px-2 py-1.5 rounded-lg mb-0.5 transition-all duration-150 text-left"
                :class="activeTab === 'payments' ? 'bg-teal-50 ring-1 ring-teal-100 shadow-sm' : 'hover:bg-slate-50'">
                <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0 transition-all duration-150"
                    :class="activeTab === 'payments' ? 'bg-teal-500 shadow-sm shadow-teal-200' : 'bg-slate-100 group-hover:bg-slate-200'">
                    <svg class="w-3 h-3 transition-colors" :class="activeTab === 'payments' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="text-xs transition-colors" :class="activeTab === 'payments' ? 'font-bold text-teal-700' : 'font-medium text-slate-500 group-hover:text-slate-700'">Payments</span>
            </button>

        </aside>

        <!-- Tab Content Area -->
        <div class="flex-1 min-w-0 px-8 py-6 overflow-auto bg-slate-50/60">

            <!-- Send to Vendor Banner (Pending invoices) -->
            @if($canManage && $invoice->status === \App\Enums\InvoiceStatus::PENDING)
            <div class="mb-5 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200/80 rounded-2xl p-5 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5.5 h-5.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-900">Invoice Not Yet Sent</p>
                        <p class="text-xs text-slate-500 mt-0.5">This invoice is still pending. Send it to the vendor for review and acceptance.</p>
                    </div>
                </div>
                <button @@click="sendInvoice()" :disabled="actionLoading"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span x-text="actionLoading ? 'Sending...' : 'Send to Vendor'">Send to Vendor</span>
                </button>
            </div>
            @endif

            <!-- ═══ DETAILS TAB ═══ -->
            <div x-show="activeTab === 'details'" x-cloak>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

                    <!-- Invoice Details Card -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-sm font-bold text-slate-900">Invoice Information</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Invoice #</span>
                                <span class="text-xs font-bold text-slate-900">{{ $invoice->invoice_number }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Status</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    @switch($invoice->status->value)
                                        @case('pending') bg-amber-100 text-amber-700 @break
                                        @case('sent') bg-blue-100 text-blue-700 @break
                                        @case('accepted') bg-emerald-100 text-emerald-700 @break
                                        @case('rejected') bg-rose-100 text-rose-700 @break
                                        @case('cancelled') bg-gray-100 text-gray-500 @break
                                    @endswitch
                                ">{{ $invoice->status->label() }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Currency</span>
                                <span class="text-xs font-semibold text-slate-900">{{ $invoice->currency ?: 'GHS' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Total Amount</span>
                                <span class="text-xs font-bold text-orange-600">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->total_amount, 2) }}</span>
                            </div>

                            <div class="border-t border-slate-100 pt-3 mt-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Timeline</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Created</span>
                                <span class="text-xs font-medium text-slate-700">{{ $invoice->created_at->format('M d, Y h:i A') }}</span>
                            </div>
                            @if($invoice->sent_at)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Sent</span>
                                <span class="text-xs font-medium text-slate-700">{{ $invoice->sent_at->format('M d, Y h:i A') }}</span>
                            </div>
                            @endif
                            @if($invoice->accepted_at)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Accepted</span>
                                <span class="text-xs font-medium text-emerald-700">{{ $invoice->accepted_at->format('M d, Y h:i A') }}</span>
                            </div>
                            @endif
                            @if($invoice->rejected_at)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Rejected</span>
                                <span class="text-xs font-medium text-rose-600">{{ $invoice->rejected_at->format('M d, Y h:i A') }}</span>
                            </div>
                            @endif
                            @if($invoice->cancelled_at)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Cancelled</span>
                                <span class="text-xs font-medium text-gray-500">{{ $invoice->cancelled_at->format('M d, Y h:i A') }}</span>
                            </div>
                            @endif
                            @if($invoice->creator)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-500">Created By</span>
                                <span class="text-xs font-medium text-slate-700">{{ $invoice->creator->name }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Shipment & Vendor Card + Notes -->
                    <div class="space-y-5">
                        @if($invoice->shipment)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <h3 class="text-sm font-bold text-slate-900">Shipment & Vendor</h3>
                            </div>
                            <div class="p-5 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500">Shipment #</span>
                                    <span class="text-xs font-bold text-slate-900">{{ $invoice->shipment->shipment_number }}</span>
                                </div>
                                @if($invoice->shipment->vendor)
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500">Vendor</span>
                                    <span class="text-xs font-semibold text-slate-900">{{ $invoice->shipment->vendor->name }}</span>
                                </div>
                                @if($invoice->shipment->vendor->business_name)
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500">Business</span>
                                    <span class="text-xs font-medium text-slate-700">{{ $invoice->shipment->vendor->business_name }}</span>
                                </div>
                                @endif
                                @if($invoice->shipment->vendor->phone)
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500">Phone</span>
                                    <span class="text-xs font-medium text-slate-700">{{ $invoice->shipment->vendor->phone }}</span>
                                </div>
                                @endif
                                @if($invoice->shipment->vendor->email)
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500">Email</span>
                                    <span class="text-xs font-medium text-slate-700">{{ $invoice->shipment->vendor->email }}</span>
                                </div>
                                @endif
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Notes -->
                        @if($invoice->notes || $invoice->vendor_notes || $invoice->rejection_reason || $invoice->cancel_reason)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                                <h3 class="text-sm font-bold text-slate-900">Notes</h3>
                            </div>
                            <div class="p-5 space-y-3">
                                @if($invoice->notes)
                                <div class="p-3 bg-slate-50 rounded-xl border-l-4 border-slate-300">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Admin Notes</p>
                                    <p class="text-xs text-slate-700">{{ $invoice->notes }}</p>
                                </div>
                                @endif
                                @if($invoice->vendor_notes)
                                <div class="p-3 bg-blue-50 rounded-xl border-l-4 border-blue-300">
                                    <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider mb-1">Vendor Notes</p>
                                    <p class="text-xs text-slate-700">{{ $invoice->vendor_notes }}</p>
                                </div>
                                @endif
                                @if($invoice->rejection_reason)
                                <div class="p-3 bg-rose-50 rounded-xl border-l-4 border-rose-400">
                                    <p class="text-[10px] font-bold text-rose-500 uppercase tracking-wider mb-1">Rejection Reason</p>
                                    <p class="text-xs text-slate-700">{{ $invoice->rejection_reason }}</p>
                                </div>
                                @endif
                                @if($invoice->cancel_reason)
                                <div class="p-3 bg-rose-50 rounded-xl border-l-4 border-rose-400">
                                    <p class="text-[10px] font-bold text-rose-500 uppercase tracking-wider mb-1">Cancel Reason</p>
                                    <p class="text-xs text-slate-700">{{ $invoice->cancel_reason }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                </div>
            </div>

            <!-- ═══ FEE BREAKDOWN TAB ═══ -->
            <div x-show="activeTab === 'fees'" x-cloak>
                <div class="flex justify-center">
                    <div class="w-full max-w-xl">

                        <!-- Header Card -->
                        <div class="bg-gradient-to-br from-sky-500 to-indigo-600 rounded-2xl p-6 mb-5 shadow-lg shadow-sky-200/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sky-100 text-xs font-semibold uppercase tracking-wider mb-1">Invoice Total</p>
                                    <p class="text-3xl font-extrabold text-white">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->total_amount, 2) }}</p>
                                </div>
                                <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Fee Items -->
                        <div class="space-y-3">
                            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow p-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Pickup Fee</p>
                                    <p class="text-[11px] text-slate-400">Collection from sender</p>
                                </div>
                                <p class="text-sm font-bold text-slate-900 tabular-nums">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->pickup_fee, 2) }}</p>
                            </div>

                            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow p-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Transport Fee</p>
                                    <p class="text-[11px] text-slate-400">Shipment transportation</p>
                                </div>
                                <p class="text-sm font-bold text-slate-900 tabular-nums">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->transport_fee, 2) }}</p>
                            </div>

                            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow p-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Handling Fee</p>
                                    <p class="text-[11px] text-slate-400">Package handling & processing</p>
                                </div>
                                <p class="text-sm font-bold text-slate-900 tabular-nums">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->handling_fee, 2) }}</p>
                            </div>

                            @if((float) $invoice->other_fee > 0)
                            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow p-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">Other Fee</p>
                                    <p class="text-[11px] text-slate-400">Additional charges</p>
                                </div>
                                <p class="text-sm font-bold text-slate-900 tabular-nums">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->other_fee, 2) }}</p>
                            </div>
                            @endif
                        </div>

                        <!-- Total Bar -->
                        <div class="mt-4 bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl border-2 border-orange-200 p-5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-orange-500 shadow-md shadow-orange-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-base font-bold text-slate-900">Total Amount</span>
                            </div>
                            <span class="text-2xl font-extrabold text-orange-600 tabular-nums">{{ $invoice->currency ?: 'GHS' }} {{ number_format((float) $invoice->total_amount, 2) }}</span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ═══ PAYMENTS TAB ═══ -->
            <div x-show="activeTab === 'payments'" x-cloak>

                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
                    <!-- Card Header -->
                    <div class="px-6 py-5 border-b border-slate-200/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-teal-100">
                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-900">Payments</h2>
                                    <p class="mt-0.5 text-sm text-slate-500">Payment transactions for this invoice</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="filteredPayments().length + ' Total Payments'"></span>
                                @if($canManage)
                                <button @@click="paymentForm.open = true; paymentForm.payment_date = new Date().toISOString().split('T')[0]" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-xs font-semibold rounded-xl hover:bg-slate-800 transition-colors shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Record Payment
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Table Controls -->
                    <div class="p-6 pb-0">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="relative w-full sm:w-64">
                                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input type="text" x-model="paymentSearch" @@input="paymentPage = 1" placeholder="Search payments..." class="w-full pl-10 pr-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <!-- Export -->
                                <div x-data="{ open: false }" class="relative">
                                    <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Export
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                        <button type="button" @@click="exportPayments('csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            CSV
                                        </button>
                                        <div class="border-t border-slate-200/50 my-1"></div>
                                        <button type="button" @@click="printPayments(); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            Print
                                        </button>
                                    </div>
                                </div>

                                <!-- View/Columns -->
                                <div x-data="{ open: false }" class="relative">
                                    <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                        View
                                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                                        <template x-for="col in paymentColumns" :key="col.key">
                                            <button type="button" @@click="togglePaymentColumn(col.key)" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                                <span x-text="col.label"></span>
                                                <svg x-show="paymentVisibleColumns[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="px-6 py-4">
                        <div class="rounded-xl border border-slate-200/50 relative">
                            <div x-show="!paymentsLoaded" x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center" style="display: none;">
                                <div class="flex items-center gap-2 text-slate-400 text-sm">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Loading payments...
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[900px] md:min-w-full divide-y divide-slate-200/50 text-xs">
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th x-show="paymentVisibleColumns.payment_date" @@click="sortPayments('payment_date')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">DATE <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'payment_date' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                            </th>
                                            <th x-show="paymentVisibleColumns.amount" @@click="sortPayments('amount')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">AMOUNT <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'amount' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                            </th>
                                            <th x-show="paymentVisibleColumns.method" @@click="sortPayments('method_label')" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider cursor-pointer">
                                                <div class="flex items-center">METHOD <svg class="w-2.5 h-2.5 ml-1" :class="paymentSortBy === 'method_label' ? 'text-slate-600' : 'text-slate-400 opacity-50'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5-5 5 5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l5 5 5-5"/></svg></div>
                                            </th>
                                            <th x-show="paymentVisibleColumns.reference" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">REFERENCE</th>
                                            <th x-show="paymentVisibleColumns.recorded_by" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">RECORDED BY</th>
                                            <th x-show="paymentVisibleColumns.notes" class="px-4 py-2 text-left text-[10px] font-semibold text-slate-500 uppercase tracking-wider">NOTES</th>
                                            <th x-show="paymentVisibleColumns.actions" class="px-4 py-2 text-center text-[10px] font-semibold text-slate-500 uppercase tracking-wider">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-transparent divide-y divide-slate-100/50">
                                        <template x-if="paymentsLoaded && filteredPayments().length === 0">
                                            <tr>
                                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 text-xs">
                                                    <div class="flex flex-col items-center gap-2">
                                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                        </svg>
                                                        <span>No payments found</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>

                                        <template x-for="payment in paginatedPayments()" :key="payment.id">
                                            <tr class="hover:bg-slate-50/70 transition-colors">
                                                <td x-show="paymentVisibleColumns.payment_date" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="payment.payment_date"></td>
                                                <td x-show="paymentVisibleColumns.amount" class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="text-xs font-semibold text-emerald-700" x-text="'{{ $invoice->currency ?: 'GHS' }} ' + payment.formatted_amount"></span>
                                                </td>
                                                <td x-show="paymentVisibleColumns.method" class="px-4 py-2.5 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-700" x-text="payment.method_label"></span>
                                                </td>
                                                <td x-show="paymentVisibleColumns.reference" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-500 font-mono" x-text="payment.reference_number || '\u2014'"></td>
                                                <td x-show="paymentVisibleColumns.recorded_by" class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-600" x-text="payment.recorded_by || '\u2014'"></td>
                                                <td x-show="paymentVisibleColumns.notes" class="px-4 py-2.5 text-xs text-slate-500 max-w-[150px] truncate" x-text="payment.notes || '\u2014'"></td>
                                                <td x-show="paymentVisibleColumns.actions" class="px-4 py-2.5 whitespace-nowrap">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <a :href="config.downloadReceiptUrlTemplate.replace('__PAYMENT__', payment.id)" target="_blank"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition-colors inline-flex" title="Download receipt PDF">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                        </a>
                                                        <a :href="config.printReceiptUrlTemplate.replace('__PAYMENT__', payment.id)" target="_blank"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors inline-flex" title="Print receipt">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                        </a>
                                                        <button x-show="isSuperAdmin" @@click="voidPayment(payment.id)"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors inline-flex" title="Void payment">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div x-show="paymentsLoaded && filteredPayments().length > 0" class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="text-xs text-slate-600">
                                        Showing <span x-text="Math.min(((paymentPage - 1) * paymentPerPage) + 1, filteredPayments().length)"></span>
                                        to <span x-text="Math.min(paymentPage * paymentPerPage, filteredPayments().length)"></span>
                                        of <span x-text="filteredPayments().length"></span> results
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium text-slate-600">Rows per page</span>
                                            <div x-data="{ open: false }" class="relative">
                                                <button type="button" @@click="open = !open" class="inline-flex items-center justify-between gap-1.5 px-2.5 py-1 min-w-[60px] border border-slate-200/70 rounded-lg bg-white/70 backdrop-blur-sm text-xs font-medium text-slate-700 hover:bg-white/90 transition-colors">
                                                    <span x-text="paymentPerPage"></span>
                                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                                <div x-show="open" @@click.away="open = false" x-transition class="absolute bottom-full mb-1 right-0 w-16 rounded-lg border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-lg p-1 z-[9999]" style="display: none;">
                                                    <button type="button" @@click="paymentPerPage = 10; paymentPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="paymentPerPage == 10 ? 'bg-slate-100/70' : ''">10</button>
                                                    <button type="button" @@click="paymentPerPage = 25; paymentPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="paymentPerPage == 25 ? 'bg-slate-100/70' : ''">25</button>
                                                    <button type="button" @@click="paymentPerPage = 50; paymentPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="paymentPerPage == 50 ? 'bg-slate-100/70' : ''">50</button>
                                                    <button type="button" @@click="paymentPerPage = 100; paymentPage = 1; open = false" class="w-full text-center px-2 py-1 rounded text-xs font-medium text-slate-700 hover:bg-slate-100/70" :class="paymentPerPage == 100 ? 'bg-slate-100/70' : ''">100</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-xs font-medium text-slate-600">Page <span x-text="paymentPage"></span> of <span x-text="paymentLastPage()"></span></div>
                                        <div class="flex space-x-1">
                                            <button @@click="paymentPage = 1" :disabled="paymentPage === 1" :class="paymentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = Math.max(1, paymentPage - 1)" :disabled="paymentPage === 1" :class="paymentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = Math.min(paymentLastPage(), paymentPage + 1)" :disabled="paymentPage >= paymentLastPage()" :class="paymentPage >= paymentLastPage() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                            <button @@click="paymentPage = paymentLastPage()" :disabled="paymentPage >= paymentLastPage()" :class="paymentPage >= paymentLastPage() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'" class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- ══ MODAL: Record Payment ═══════════════════════════ -->
    <div x-show="paymentForm.open" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="paymentForm.open = false"></div>
        <div class="relative z-10 bg-white w-full sm:rounded-2xl sm:max-w-lg shadow-2xl max-h-[95vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center">
                        <svg class="w-4.5 h-4.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Record Payment</h3>
                </div>
                <button @@click="paymentForm.open = false" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @@submit.prevent="submitPayment()" class="px-6 py-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Amount <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" x-model="paymentForm.amount" required
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Payment Method <span class="text-rose-500">*</span></label>
                        <select x-model="paymentForm.payment_method" required
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300">
                            <option value="">Select method</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                        <input type="date" x-model="paymentForm.payment_date" required
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Reference Number</label>
                        <input type="text" x-model="paymentForm.reference_number"
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300" placeholder="Optional">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Notes</label>
                        <textarea x-model="paymentForm.notes" rows="2"
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-400/50 focus:border-teal-300" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-slate-100">
                    <button type="button" @@click="paymentForm.open = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" :disabled="paymentForm.submitting"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-600 hover:to-emerald-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-teal-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <svg x-show="paymentForm.submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="paymentForm.submitting ? 'Saving...' : 'Record Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Confirm Modal -->
    <div x-show="confirmModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @@click="confirmModalCancel()"></div>
        <div class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900" x-text="confirmModal.title"></h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed" x-text="confirmModal.message"></p>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" @@click="confirmModalCancel()" :disabled="confirmModal.loading"
                    class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors disabled:opacity-50">
                    Cancel
                </button>
                <button type="button" @@click="confirmModalOk()" :disabled="confirmModal.loading"
                    :class="confirmModal.confirmClass"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="confirmModal.loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="confirmModal.loading ? 'Please wait...' : confirmModal.confirmLabel"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function invoiceShow() {
    return {
        config: {},
        invoice: {},
        canManage: false,
        isSuperAdmin: false,
        actionLoading: false,
        activeTab: 'details',

        paymentsLoaded: false,
        paymentsData: { payments: [], summary: { total_invoiced: 0, total_paid: 0, balance_due: 0 } },
        paymentSearch: '',
        paymentSortBy: 'payment_date',
        paymentSortDir: 'desc',
        paymentPage: 1,
        paymentPerPage: 10,
        paymentColumns: [
            { key: 'payment_date', label: 'Date' },
            { key: 'amount', label: 'Amount' },
            { key: 'method', label: 'Method' },
            { key: 'reference', label: 'Reference' },
            { key: 'recorded_by', label: 'Recorded By' },
            { key: 'notes', label: 'Notes' },
            { key: 'actions', label: 'Actions' },
        ],
        paymentVisibleColumns: {
            payment_date: true, amount: true, method: true, reference: true,
            recorded_by: true, notes: true, actions: true,
        },
        paymentForm: {
            open: false, submitting: false, amount: '', payment_method: '',
            reference_number: '', notes: '', payment_date: '',
        },
        confirmModal: {
            open: false, title: '', message: '', confirmLabel: 'Confirm',
            confirmClass: 'bg-rose-600 hover:bg-rose-700 text-white', loading: false, _resolve: null,
        },

        init() {
            const el = this.$el.closest('[data-invoice-show-config]');
            if (el) {
                try {
                    this.config = JSON.parse(el.getAttribute('data-invoice-show-config'));
                    this.invoice = this.config.invoice || {};
                    this.canManage = this.config.canManage || false;
                    this.isSuperAdmin = this.config.isSuperAdmin || false;
                } catch (e) {
                    console.error('Failed to parse invoice config:', e);
                }
            }
            this.loadPayments();
        },

        async loadPayments() {
            try {
                const response = await fetch(this.config.paymentsDataEndpoint, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const data = await response.json();
                this.paymentsData = data;
                this.paymentsLoaded = true;
            } catch (e) {
                console.error('Failed to load payments:', e);
                this.paymentsLoaded = true;
            }
        },

        filteredPayments() {
            let list = this.paymentsData.payments || [];
            if (this.paymentSearch) {
                const q = this.paymentSearch.toLowerCase();
                list = list.filter(p =>
                    (p.payment_date || '').toLowerCase().includes(q) ||
                    (p.method_label || '').toLowerCase().includes(q) ||
                    (p.reference_number || '').toLowerCase().includes(q) ||
                    (p.recorded_by || '').toLowerCase().includes(q) ||
                    (p.notes || '').toLowerCase().includes(q) ||
                    String(p.amount).includes(q)
                );
            }
            const dir = this.paymentSortDir === 'asc' ? 1 : -1;
            const key = this.paymentSortBy;
            list = [...list].sort((a, b) => {
                const av = key === 'amount' ? parseFloat(a[key]) : (a[key] || '');
                const bv = key === 'amount' ? parseFloat(b[key]) : (b[key] || '');
                if (av < bv) return -1 * dir;
                if (av > bv) return 1 * dir;
                return 0;
            });
            return list;
        },

        paginatedPayments() {
            const all = this.filteredPayments();
            const start = (this.paymentPage - 1) * this.paymentPerPage;
            return all.slice(start, start + this.paymentPerPage);
        },

        paymentLastPage() {
            return Math.max(1, Math.ceil(this.filteredPayments().length / this.paymentPerPage));
        },

        sortPayments(column) {
            if (this.paymentSortBy === column) {
                this.paymentSortDir = this.paymentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.paymentSortBy = column;
                this.paymentSortDir = 'asc';
            }
            this.paymentPage = 1;
        },

        togglePaymentColumn(key) {
            this.paymentVisibleColumns[key] = !this.paymentVisibleColumns[key];
        },

        exportPayments(format) {
            const data = this.filteredPayments();
            if (!data.length) { alert('No data to export'); return; }

            const rows = data.map(p => ({
                'Date': p.payment_date || '',
                'Amount': p.formatted_amount || p.amount || '',
                'Method': p.method_label || '',
                'Reference': p.reference_number || '',
                'Recorded By': p.recorded_by || '',
                'Notes': p.notes || '',
            }));

            if (format === 'csv') {
                const headers = Object.keys(rows[0]);
                const csvContent = [
                    headers.join(','),
                    ...rows.map(row => headers.map(h => { let cell = String(row[h] ?? '').replace(/"/g, '""'); return `"${cell}"`; }).join(','))
                ].join('\n');

                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'invoice-payments.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }
        },

        printPayments() {
            const data = this.filteredPayments();
            if (!data.length) { alert('No data to print'); return; }
            const printWindow = window.open('', '_blank');
            if (!printWindow) { alert('Pop-up blocked.'); return; }
            const currency = '{{ $invoice->currency ?: "GHS" }}';
            const headers = ['Date', 'Amount', 'Method', 'Reference', 'Recorded By', 'Notes'];
            const doc = printWindow.document;
            doc.title = 'Invoice Payments';
            doc.body.innerHTML = '';
            const style = doc.createElement('style');
            style.textContent = 'body{font-family:Arial,sans-serif;padding:20px}h1{font-size:24px;margin-bottom:20px;color:#1e293b}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #e2e8f0;padding:8px 12px;text-align:left;font-size:12px}th{background:#f1f5f9;font-weight:600;color:#475569}tr:nth-child(even){background:#f8fafc}';
            doc.head.appendChild(style);
            const title = doc.createElement('h1');
            title.textContent = 'Invoice Payments \u2014 ' + (this.invoice.invoice_number || '');
            doc.body.appendChild(title);
            const table = doc.createElement('table');
            const thead = doc.createElement('thead');
            const headRow = doc.createElement('tr');
            headers.forEach(h => { const th = doc.createElement('th'); th.textContent = h; headRow.appendChild(th); });
            thead.appendChild(headRow); table.appendChild(thead);
            const tbody = doc.createElement('tbody');
            data.forEach(p => {
                const tr = doc.createElement('tr');
                [p.payment_date, currency + ' ' + (p.formatted_amount || p.amount), p.method_label, p.reference_number || '\u2014', p.recorded_by || '\u2014', p.notes || '\u2014'].forEach(val => {
                    const td = doc.createElement('td'); td.textContent = val || '\u2014'; tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody); doc.body.appendChild(table);
            setTimeout(() => printWindow.print(), 250);
        },

        async submitPayment() {
            this.paymentForm.submitting = true;
            try {
                const response = await fetch(this.config.storePaymentEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({
                        amount: this.paymentForm.amount,
                        payment_method: this.paymentForm.payment_method,
                        reference_number: this.paymentForm.reference_number || null,
                        notes: this.paymentForm.notes || null,
                        payment_date: this.paymentForm.payment_date,
                    }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to record payment');
                if (window.showToast) window.showToast(data.message || 'Payment recorded', 'success');
                this.paymentForm = { open: false, submitting: false, amount: '', payment_method: '', reference_number: '', notes: '', payment_date: '' };
                await this.loadPayments();
            } catch (e) {
                console.error('Failed to record payment:', e);
                if (window.showToast) window.showToast(e.message || 'Failed to record payment', 'error');
            } finally {
                this.paymentForm.submitting = false;
            }
        },

        showConfirm(title, message, confirmLabel = 'Confirm', confirmClass = 'bg-rose-600 hover:bg-rose-700 text-white') {
            return new Promise(resolve => {
                this.confirmModal = { open: true, title, message, confirmLabel, confirmClass, loading: false, _resolve: resolve };
            });
        },

        async confirmModalOk() {
            this.confirmModal.loading = true;
            this.confirmModal._resolve(true);
        },

        confirmModalCancel() {
            this.confirmModal.open = false;
            this.confirmModal._resolve(false);
        },

        async voidPayment(paymentId) {
            if (!await this.showConfirm('Void this payment?', 'This action is permanent and cannot be undone. The payment record will be removed.', 'Yes, Void Payment')) return;
            try {
                const endpoint = (this.config.destroyPaymentEndpointTemplate || '').replace('__PAYMENT__', paymentId);
                const response = await fetch(endpoint, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to void payment');
                this.confirmModal.open = false;
                if (window.showToast) window.showToast(data.message || 'Payment voided', 'success');
                await this.loadPayments();
            } catch (e) {
                console.error('Failed to void payment:', e);
                if (window.showToast) window.showToast(e.message || 'Failed to void payment', 'error');
            } finally {
                this.confirmModal.loading = false;
            }
        },

        async sendInvoice() {
            if (!await this.showConfirm('Send this invoice?', 'The invoice will be sent to the vendor for review and acceptance.', 'Send Invoice', 'bg-blue-600 hover:bg-blue-700 text-white')) return;
            this.actionLoading = true;
            try {
                const response = await fetch(this.config.sendEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to send invoice');
                if (window.showToast) window.showToast(data.message || 'Invoice sent', 'success');
                setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                console.error('Failed to send invoice:', e);
                if (window.showToast) window.showToast(e.message || 'Failed to send invoice', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        async acceptInvoice() {
            if (!await this.showConfirm('Accept on behalf of vendor?', 'This will mark the invoice as accepted. You are acting on behalf of the vendor.', 'Yes, Accept', 'bg-emerald-600 hover:bg-emerald-700 text-white')) return;
            this.actionLoading = true;
            try {
                const response = await fetch(this.config.adminAcceptEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to accept invoice');
                if (window.showToast) window.showToast(data.message || 'Invoice accepted', 'success');
                setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                console.error('Failed to accept invoice:', e);
                if (window.showToast) window.showToast(e.message || 'Failed to accept invoice', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        async cancelInvoice() {
            const reason = prompt('Cancel reason (optional):');
            if (reason === null) return;
            this.actionLoading = true;
            try {
                const response = await fetch(this.config.cancelEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ cancel_reason: reason || null }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to cancel invoice');
                if (window.showToast) window.showToast(data.message || 'Invoice cancelled', 'success');
                setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                console.error('Failed to cancel invoice:', e);
                if (window.showToast) window.showToast(e.message || 'Failed to cancel invoice', 'error');
            } finally {
                this.actionLoading = false;
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('invoiceShow', invoiceShow);
});
</script>
@endpush
