@extends('web.layouts.vendor')

@section('title', 'Shipments')
@section('page-title', 'Shipments')

@section('content')
<div x-data="vendorShipmentsListPage()">
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Manage and track all your shipments</p>
        <a href="{{ route('web.vendor.shipments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Shipment
        </a>
    </div>

    {{-- Filters --}}
    <div class="vendor-card mb-6 p-4">
        <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="applyFilters()">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-500">Search</label>
                <input x-model="filters.search" type="text" placeholder="Shipment number, contact name..." class="vendor-input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                <select x-model="filters.statuses" multiple class="vendor-input h-[100px]">
                    <template x-for="status in statuses" :key="status.value">
                        <option :value="status.value" x-text="status.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Date Range</label>
                <div class="space-y-2">
                    <input x-model="filters.from_date" type="date" class="vendor-input" placeholder="From">
                    <input x-model="filters.to_date" type="date" class="vendor-input" placeholder="To">
                </div>
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
                            <th>Shipment #</th>
                            <th>Status</th>
                            <th>Mode</th>
                            <th>Pickup Contact</th>
                            <th>Items</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="shipment in shipments" :key="shipment.id">
                            <tr>
                                <td class="font-semibold text-slate-900" x-text="shipment.shipment_number"></td>
                                <td>
                                    <span class="vendor-badge" :class="'vendor-badge-' + shipment.status" x-text="statusLabel(shipment.status)"></span>
                                </td>
                                <td x-text="modeLabel(shipment.destination_mode)"></td>
                                <td>
                                    <div class="font-medium text-slate-900" x-text="shipment.pickup?.contact_name || '-'"></div>
                                    <div class="text-xs text-slate-400" x-text="shipment.pickup?.contact_phone || ''"></div>
                                </td>
                                <td x-text="shipment.items?.length ?? 0"></td>
                                <td class="text-xs" x-text="formatDateTime(shipment.submitted_at)"></td>
                                <td>
                                    <div class="flex flex-wrap gap-1.5">
                                        <a :href="`/vendor/shipments/${shipment.id}`" class="rounded border border-gray-200 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-gray-50">View</a>
                                        <a x-show="shipment.can_edit" :href="`/vendor/shipments/${shipment.id}/edit`" class="rounded border border-slate-200 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Edit</a>
                                        <button x-show="shipment.can_submit" type="button" @click="submitShipment(shipment)" class="rounded border border-green-200 px-2 py-1 text-xs font-medium text-green-600 hover:bg-green-50">Submit</button>
                                        <button x-show="shipment.can_delete" type="button" @click="deleteShipment(shipment)" class="rounded border border-red-200 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="shipments.length === 0" class="px-6 py-12 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                <p class="mt-2 text-sm text-slate-500">No shipments found</p>
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
