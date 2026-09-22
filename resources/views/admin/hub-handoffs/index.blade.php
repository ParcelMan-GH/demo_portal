@extends($layoutName ?? 'admin.layouts.app')

@section('title', 'Bus Handovers')
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Bus Handovers')

@section('content')
<div class="space-y-6">
    {{-- Summary --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Handovers recorded</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($summary['total']) }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-500">Matching your filters</p>
        </div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Today</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($summary['today']) }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-500">Handed to a bus today</p>
        </div>
        <div class="overflow-hidden rounded-2xl border {{ $summary['sms_failed'] > 0 ? 'border-rose-200' : 'border-slate-200' }} bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Customer not texted</p>
            <p class="mt-2 text-3xl font-black {{ $summary['sms_failed'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ number_format($summary['sms_failed']) }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-500">SMS failed — customer has no photo link</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0-4-4m4 4-4 4m0 6H4m0 0 4 4m-4-4 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Bus Handovers</h2>
                        <p class="mt-0.5 text-sm font-medium text-slate-500">Parcels handed to external bus drivers, with the handover photo</p>
                    </div>
                </div>
                <span class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-sm font-black text-orange-700">
                    {{ number_format($handoffs->total()) }} total
                </span>
            </div>
        </div>

        {{-- Filters: a plain GET form, so the state lives in the URL and a filtered view can be shared --}}
        <form method="GET" action="{{ route('admin.hub-handoffs.index') }}" class="border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Tracking code, driver, vehicle..."
                    class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                >

                <select name="hub_id" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    <option value="">All hubs</option>
                    @foreach($hubs as $hub)
                        <option value="{{ $hub->id }}" @selected((string) ($filters['hub_id'] ?? '') === (string) $hub->id)>{{ $hub->name }}</option>
                    @endforeach
                </select>

                <select name="sms" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    <option value="">Any SMS state</option>
                    <option value="sent" @selected(($filters['sms'] ?? '') === 'sent')>Customer texted</option>
                    <option value="failed" @selected(($filters['sms'] ?? '') === 'failed')>SMS failed</option>
                </select>

                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-orange-600 px-4 py-3 text-sm font-black text-white transition hover:bg-orange-700">
                        Filter
                    </button>
                    <a href="{{ route('admin.hub-handoffs.index') }}" class="inline-flex items-center justify-center rounded-xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        @if($handoffs->isEmpty())
            <div class="px-5 py-16 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0-4-4m4 4-4 4m0 6H4m0 0 4 4m-4-4 4-4"/>
                    </svg>
                </div>
                <p class="mt-5 text-lg font-black text-slate-900">No handovers found</p>
                <p class="mx-auto mt-1 max-w-sm text-sm font-semibold text-slate-500">Nothing matches these filters yet. Handovers appear here as soon as a bus handoff agent submits one.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-[11px] font-black uppercase tracking-[0.12em] text-slate-500">
                            <th class="px-4 py-3">Package</th>
                            <th class="px-4 py-3">Destination</th>
                            <th class="px-4 py-3">Driver / vehicle</th>
                            <th class="px-4 py-3">Hub</th>
                            <th class="px-4 py-3">Handed over</th>
                            <th class="px-4 py-3">Customer SMS</th>
                            <th class="px-4 py-3 text-right">Photo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($handoffs as $handoff)
                            @php
                                $package = $handoff['package'] ?? [];
                                $driver = $handoff['driver'] ?? [];
                                $vehicle = $handoff['vehicle'] ?? [];
                                $sms = $handoff['sms'] ?? [];
                                $handedOffAt = ! empty($handoff['handed_off_at'])
                                    ? \Illuminate\Support\Carbon::parse($handoff['handed_off_at'])
                                    : null;
                            @endphp
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.hub-handoffs.show', $handoff['id']) }}" class="font-mono text-sm font-black text-orange-700 hover:underline">
                                        {{ $package['tracking_code'] ?? '—' }}
                                    </a>
                                    <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ \Illuminate\Support\Str::limit($package['description'] ?? '', 32) ?: '—' }}</p>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-700">{{ $handoff['destination'] ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-slate-900">{{ $driver['name'] ?? '—' }}</p>
                                    <p class="mt-0.5 text-xs font-semibold text-slate-500">
                                        {{ collect([$vehicle['plate'] ?? null, $vehicle['company'] ?? null])->filter()->implode(' · ') ?: '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-700">{{ $handoff['hub']['name'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-700">{{ $handedOffAt?->format('d M Y') }}</p>
                                    <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $handedOffAt?->format('h:i A') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if(! empty($sms['sent_at']))
                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700">Sent</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-black text-rose-700">Failed</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($handoff['has_proof_photo'] ?? false)
                                        <a href="{{ route('admin.hub-handoffs.show', $handoff['id']) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-black text-slate-700 transition hover:bg-slate-50">
                                            <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z"/>
                                            </svg>
                                            View
                                        </a>
                                    @else
                                        <span class="text-xs font-bold text-slate-400">None</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($handoffs->hasPages())
                <div class="border-t border-slate-100 px-4 py-4 sm:px-5">
                    {{ $handoffs->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
