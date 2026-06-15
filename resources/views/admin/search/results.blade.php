@extends('admin.layouts.app')

@section('title', 'Search Results')
@section('breadcrumb-parent', 'Search')
@section('breadcrumb-current', 'Results')

@php
    $tabLabels = [
        'shipments' => 'Shipments',
        'packages' => 'Packages',
        'vendors' => 'Vendors',
        'drivers' => 'Riders',
        'transactions' => 'Transactions',
    ];
    $tabUrl = fn (string $tab) => route('admin.search.results', array_filter([
        'q' => $q,
        'type' => $tab,
        'status' => $tab === 'shipments' ? $filters['status'] : null,
        'date_from' => $filters['date_from'],
        'date_to' => $filters['date_to'],
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

@section('content')
<div class="space-y-5">

    {{-- Search form --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <h1 class="text-lg font-extrabold text-slate-900">Advanced Search</h1>
                    <p class="truncate text-sm text-slate-500">Search shipments, packages, tracking numbers, vendors, riders and transactions. Tip: prefixes like <span class="font-bold">vendor:</span> or <span class="font-bold">trk:</span> jump straight to a category.</p>
                </div>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.search.results') }}" class="grid gap-3 p-5 lg:grid-cols-[minmax(0,1fr)_160px_160px_160px_auto]">
            <input type="hidden" name="type" value="{{ $type }}">
            <label class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Tracking number, phone, name, shipment number…" autofocus
                       class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-9 pr-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
            </label>
            @if($type === 'shipments')
                <select name="status" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    <option value="">Any status</option>
                    @foreach($shipmentStatuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ ucwords(str_replace('_', ' ', $status->value)) }}</option>
                    @endforeach
                </select>
            @else
                <div class="hidden lg:block"></div>
            @endif
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" title="From date"
                   class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" title="To date"
                   class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                Search
            </button>
        </form>
    </section>

    @if(!$hasQuery)
        <section class="rounded-3xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Type at least two characters to search.</p>
        </section>
    @else
        {{-- Category tabs --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                @foreach($tabs as $tab)
                    <a href="{{ $tabUrl($tab) }}"
                       class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black transition {{ $type === $tab ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50' }}">
                        {{ $tabLabels[$tab] ?? ucfirst($tab) }}
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-black {{ $type === $tab ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                            {{ $counts[$tab] ?? 0 }}{{ $tab === 'transactions' && ($counts[$tab] ?? 0) >= 10 ? '+' : '' }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Results --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
            @if($type === 'transactions')
                @forelse($transactionGroups as $groupLabel => $groupResults)
                    <div class="border-b border-slate-200/60 bg-slate-50/60 px-5 py-2.5">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $groupLabel }} <span class="ml-1 font-bold normal-case tracking-normal">(latest {{ $groupResults->count() }})</span></span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($groupResults as $row)
                            @include('admin.search.partials.result-row', ['row' => $row])
                        @endforeach
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="text-sm font-semibold text-slate-500">No transactions match "{{ $q }}".</p>
                    </div>
                @endforelse
            @elseif($results && $results->count())
                <div class="divide-y divide-slate-100">
                    @foreach($results as $row)
                        @include('admin.search.partials.result-row', ['row' => $row])
                    @endforeach
                </div>
                <div class="border-t border-slate-200/60 px-5 py-4">
                    {{ $results->links() }}
                </div>
            @else
                <div class="px-5 py-12 text-center">
                    <p class="text-sm font-semibold text-slate-500">No {{ strtolower($tabLabels[$type] ?? $type) }} match "{{ $q }}"{{ ($filters['date_from'] || $filters['date_to'] || $filters['status']) ? ' with the selected filters' : '' }}.</p>
                    <p class="mt-1 text-xs text-slate-400">Try fewer characters, another category tab, or clearing the filters.</p>
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
