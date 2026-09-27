@extends($layoutName ?? 'admin.layouts.app')

@section('title', 'Handover '.($handoff['package']['tracking_code'] ?? ''))
@section('breadcrumb-parent', 'Logistics')
@section('breadcrumb-current', 'Bus Handovers')

@section('content')
@php
    $package = $handoff['package'] ?? [];
    $driver = $handoff['driver'] ?? [];
    $vehicle = $handoff['vehicle'] ?? [];
    $sms = $handoff['sms'] ?? [];
    $hub = $handoff['hub'] ?? [];
    $recipient = $handoff['recipient'] ?? [];

    $handedOffAt = ! empty($handoff['handed_off_at']) ? \Illuminate\Support\Carbon::parse($handoff['handed_off_at']) : null;
    $departureAt = ! empty($handoff['departure_at']) ? \Illuminate\Support\Carbon::parse($handoff['departure_at']) : null;

    $details = array_values(array_filter([
        ['label' => 'Destination', 'value' => $handoff['destination'] ?? null],
        ['label' => 'Hub', 'value' => $hub['name'] ?? null],
        ['label' => 'Handed over', 'value' => $handedOffAt?->format('d M Y, h:i A')],
        ['label' => 'Departure', 'value' => $departureAt?->format('d M Y, h:i A')],
        ['label' => 'Recorded by', 'value' => $handoff['handed_off_by']['name'] ?? null],
    ], fn ($row) => filled($row['value'])));

    $driverRows = array_values(array_filter([
        ['label' => 'Driver name', 'value' => $driver['name'] ?? null],
        ['label' => 'Driver phone', 'value' => $driver['phone'] ?? null],
        ['label' => 'Driver ID', 'value' => $driver['id_number'] ?? null],
        ['label' => 'Vehicle plate', 'value' => $vehicle['plate'] ?? null],
        ['label' => 'Vehicle details', 'value' => $vehicle['description'] ?? null],
        ['label' => 'Bus company', 'value' => $vehicle['company'] ?? null],
    ], fn ($row) => filled($row['value'])));
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.hub-handoffs.index') }}" class="inline-flex items-center gap-1.5 text-sm font-black text-orange-700 hover:underline">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                All bus handovers
            </a>
            <h1 class="mt-2 font-mono text-2xl font-black tracking-tight text-slate-900">{{ $package['tracking_code'] ?? 'Handover' }}</h1>
            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $package['description'] ?? 'Parcelman package' }}</p>
        </div>
        @if(! empty($sms['sent_at']))
            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-black text-emerald-700">Customer texted</span>
        @else
            <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-sm font-black text-rose-700">Customer not texted</span>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
        {{-- The evidence --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                <h2 class="text-lg font-black text-slate-900">Handover photo</h2>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Taken when the parcel was handed to the bus driver. The customer was sent a link to this image.</p>
            </div>

            @if(! empty($handoff['proof_photo_url']))
                <a href="{{ $handoff['proof_photo_url'] }}" target="_blank" rel="noopener" class="block bg-slate-900/5">
                    <img src="{{ $handoff['proof_photo_url'] }}" alt="Handover photo" class="h-auto w-full object-contain">
                </a>
                <div class="border-t border-slate-100 px-4 py-3 sm:px-5">
                    <a href="{{ $handoff['proof_photo_url'] }}" target="_blank" rel="noopener" class="text-sm font-black text-orange-700 hover:underline">Open full size</a>
                </div>
            @elseif($handoff['has_proof_photo'] ?? false)
                <div class="px-4 py-10 text-center sm:px-5">
                    <p class="text-sm font-bold text-amber-700">A photo was stored, but it cannot be displayed right now.</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Check the storage configuration — the file path is recorded on this handover.</p>
                </div>
            @else
                <div class="px-4 py-10 text-center sm:px-5">
                    <p class="text-sm font-bold text-slate-600">No photo was stored for this handover.</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h2 class="text-lg font-black text-slate-900">Driver &amp; vehicle</h2>
                    <p class="mt-0.5 text-sm font-medium text-slate-500">The external driver who took the parcel. Not a Parcelman account.</p>
                </div>
                <dl class="divide-y divide-slate-100">
                    @foreach($driverRows as $row)
                        <div class="flex items-start justify-between gap-4 px-4 py-3 sm:px-5">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ $row['label'] }}</dt>
                            <dd class="text-right text-sm font-bold text-slate-900">{{ $row['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h2 class="text-lg font-black text-slate-900">Record</h2>
                </div>
                <dl class="divide-y divide-slate-100">
                    @foreach($details as $row)
                        <div class="flex items-start justify-between gap-4 px-4 py-3 sm:px-5">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ $row['label'] }}</dt>
                            <dd class="text-right text-sm font-bold text-slate-900">{{ $row['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="overflow-hidden rounded-2xl border {{ ! empty($sms['sent_at']) ? 'border-slate-200' : 'border-rose-200' }} bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                    <h2 class="text-lg font-black text-slate-900">Customer notification</h2>
                </div>
                <div class="space-y-2 px-4 py-4 sm:px-5">
                    @if(! empty($sms['sent_at']))
                        <p class="text-sm font-bold text-slate-700">Texted to {{ $recipient['phone'] ?? 'the recipient' }} on {{ \Illuminate\Support\Carbon::parse($sms['sent_at'])->format('d M Y, h:i A') }}.</p>
                        <p class="text-xs font-semibold text-slate-500">The message contained a private link to the handover photo. The link expires, and only its hash is stored.</p>
                    @else
                        <p class="text-sm font-bold text-rose-700">{{ $sms['error'] ?? 'The customer was not texted.' }}</p>
                        @if(! empty($recipient['phone']))
                            <p class="text-xs font-semibold text-slate-500">Recipient number on file: {{ $recipient['phone'] }}. The handover itself is still valid and recorded.</p>
                        @endif
                    @endif
                </div>
            </div>

            @if(! empty($handoff['notes']))
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
                        <h2 class="text-lg font-black text-slate-900">Notes from the agent</h2>
                    </div>
                    <p class="px-4 py-4 text-sm font-semibold text-slate-700 sm:px-5">{{ $handoff['notes'] }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
