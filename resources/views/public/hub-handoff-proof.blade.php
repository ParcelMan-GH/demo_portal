@php
    $package = $handoff['package'] ?? [];
    $driver = $handoff['driver'] ?? [];
    $vehicle = $handoff['vehicle'] ?? [];
    $hub = $handoff['hub'] ?? [];

    $trackingCode = $package['tracking_code'] ?? 'Package';
    $description = $package['description'] ?? 'Parcelman package';

    $handedOffAt = ! empty($handoff['handed_off_at'])
        ? \Illuminate\Support\Carbon::parse($handoff['handed_off_at'])->format('d M Y, h:i A')
        : null;

    // Only the details that prove the handover; nothing the customer should not see.
    $rows = array_values(array_filter([
        ['label' => 'Destination', 'value' => $handoff['destination'] ?? null],
        ['label' => 'Handed over', 'value' => $handedOffAt],
        ['label' => 'Hub', 'value' => $hub['name'] ?? null],
        ['label' => 'Handed to driver', 'value' => $driver['name'] ?? null],
        ['label' => 'Vehicle', 'value' => $vehicle['plate'] ?? null],
        ['label' => 'Vehicle details', 'value' => $vehicle['description'] ?? null],
        ['label' => 'Bus company', 'value' => $vehicle['company'] ?? null],
    ], fn ($row) => filled($row['value'])));
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Handover photo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950 antialiased">
    <main class="relative isolate flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:py-12">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(124,45,18,0.18),transparent_34%)]"></div>
        <div class="absolute left-0 top-0 -z-10 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-orange-200/30 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 -z-10 h-80 w-80 translate-x-1/3 translate-y-1/3 rounded-full bg-orange-900/20 blur-3xl"></div>

        <section class="w-full max-w-xl overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-slate-300/70 ring-1 ring-slate-200">
            <div class="relative overflow-hidden px-6 py-6 text-white sm:px-8"
                 style="background:linear-gradient(150deg,#7c2d12 0%,#9a3412 55%,#c2410c 100%);">
                <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-white/5"></div>
                <div class="absolute -bottom-28 -left-24 h-72 w-72 rounded-full bg-white/5"></div>

                <div class="relative z-10 flex items-start gap-4">
                    <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white/12 ring-1 ring-white/15">
                        <img src="{{ asset('logo-2.png') }}" alt="Parcelman" class="h-9 w-auto" style="filter:brightness(0) invert(1);">
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-orange-200">Parcelman Express</p>
                        <h1 class="mt-1 text-2xl font-black leading-tight sm:text-3xl">Your parcel is on the bus</h1>
                        <p class="mt-1 max-w-xl text-sm font-semibold leading-5 text-orange-100/80">Here is the photo taken when we handed it to the driver.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                @if(!$handoff)
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-12 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                        </div>
                        <p class="mt-5 text-xl font-black text-slate-900">Link unavailable</p>
                        <p class="mx-auto mt-2 max-w-sm text-sm font-semibold leading-6 text-slate-500">This link is invalid, expired, or no longer available. Please contact Parcelman support if you need the handover photo.</p>
                    </div>
                @else
                    <div class="border-b border-slate-200 pb-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700">Tracking code</p>
                        <p class="mt-1 break-words font-mono text-2xl font-black tracking-tight text-slate-950">{{ $trackingCode }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $description }}</p>
                    </div>

                    @if($photoUrl)
                        <figure class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50">
                            <img src="{{ $photoUrl }}" alt="Photo of the parcel being handed to the bus driver" class="h-auto w-full object-cover">
                            <figcaption class="border-t border-slate-200 bg-white px-4 py-3 text-xs font-bold text-slate-500">
                                Handover photo{{ $handedOffAt ? ' — '.$handedOffAt : '' }}
                            </figcaption>
                        </figure>
                    @else
                        <div class="rounded-3xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm font-bold text-amber-800">
                            The handover photo is temporarily unavailable. Please try again shortly.
                        </div>
                    @endif

                    @if($rows)
                        <dl class="divide-y divide-slate-100 overflow-hidden rounded-3xl border border-slate-200">
                            @foreach($rows as $row)
                                <div class="flex items-start justify-between gap-4 bg-white px-4 py-3">
                                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ $row['label'] }}</dt>
                                    <dd class="text-right text-sm font-bold text-slate-900">{{ $row['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    <p class="text-center text-xs font-semibold leading-5 text-slate-400">
                        Keep this link to yourself. Parcelman staff will never ask you to share it.
                    </p>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
