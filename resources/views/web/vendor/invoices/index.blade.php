@extends('web.layouts.vendor')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
<div x-data="vendorInvoicesListPage()">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">View and manage your invoices</p>
    </div>

    {{-- Filters --}}
    <div class="vendor-card mb-6 p-4">
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="applyFilters()">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Shipment</label>
                <select x-model="filters.shipment_id" @change="onShipmentChange()" class="vendor-input">
                    <option value="">All shipments</option>
                    <template x-for="shipment in shipments" :key="shipment.id">
                        <option :value="shipment.id" x-text="shipment.shipment_number"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Invoice Number</label>
                <select x-model="filters.invoice_number" class="vendor-input">
                    <option value="">All invoices</option>
                    <template x-for="invoice in invoiceOptions" :key="invoice.id">
                        <option :value="invoice.invoice_number" x-text="`${invoice.invoice_number} (${invoice.shipment_number})`"></option>
                    </template>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-500">Search</label>
                <input x-model="filters.search" type="text" placeholder="Invoice #, shipment #, status, notes..." class="vendor-input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">From Date</label>
                <input x-model="filters.from_date" type="date" class="vendor-input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">To Date</label>
                <input x-model="filters.to_date" type="date" class="vendor-input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                <select x-model="filters.status" multiple class="vendor-input h-[100px]">
                    <template x-for="status in statuses" :key="status.value">
                        <option :value="status.value" x-text="status.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Sort By</label>
                <select x-model="filters.sort_by" class="vendor-input">
                    <template x-for="field in sortFields" :key="field.value">
                        <option :value="field.value" x-text="field.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Order</label>
                <select x-model="filters.sort_order" class="vendor-input">
                    <option value="desc">Newest First</option>
                    <option value="asc">Oldest First</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Per Page</label>
                <select x-model.number="filters.limit" class="vendor-input">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Apply</button>
                <button type="button" @click="resetFilters()" class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-gray-50">Reset</button>
            </div>
        </form>
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

    {{-- Table --}}
    <div class="vendor-card overflow-hidden">
        <div x-show="loading" class="flex items-center justify-center py-16">
            <svg class="h-6 w-6 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        </div>

        <div x-show="!loading" x-cloak>
            <div class="overflow-x-auto">
                <table class="vendor-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Shipment #</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="invoice in invoices" :key="invoice.id">
                            <tr>
                                <td class="font-semibold text-slate-900" x-text="invoice.invoice_number"></td>
                                <td x-text="invoice.shipment_number || '-'"></td>
                                <td>
                                    <span class="vendor-badge" :class="'vendor-badge-' + invoice.status" x-text="statusLabel(invoice.status)"></span>
                                </td>
                                <td x-text="formatMoney(invoice.total_amount, invoice.currency)"></td>
                                <td class="text-xs" x-text="formatDateTime(invoice.created_at)"></td>
                                <td>
                                    <div class="flex flex-wrap gap-1.5">
                                        <a :href="`/vendor/invoices/${invoice.id}`" class="rounded border border-gray-200 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-gray-50">View</a>
                                        <button x-show="invoice.status === 'sent'" type="button" @click="acceptInvoice(invoice)"
                                                class="rounded border border-green-200 px-2 py-1 text-xs font-medium text-green-600 hover:bg-green-50">
                                            Accept
                                        </button>
                                        <button x-show="invoice.status === 'sent'" type="button" @click="rejectInvoice(invoice)"
                                                class="rounded border border-red-200 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Empty state --}}
            <div x-show="invoices.length === 0" class="px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <p class="mt-2 text-sm text-slate-500">No invoices found for the selected filters.</p>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 text-sm text-slate-500">
                <span>Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span> (<span x-text="pagination.total"></span> total)</span>
                <div class="flex gap-2">
                    <button type="button" @click="previousPage()" :disabled="filters.offset <= 0"
                            class="rounded border border-gray-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-gray-50 disabled:opacity-40">Previous</button>
                    <button type="button" @click="nextPage()" :disabled="!pagination.has_more"
                            class="rounded border border-gray-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-gray-50 disabled:opacity-40">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
