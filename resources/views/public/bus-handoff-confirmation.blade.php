<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Confirm Delivery</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
    <main class="mx-auto flex min-h-screen w-full max-w-2xl items-center px-4 py-8">
        <div class="w-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/40">
            <div class="bg-slate-950 px-6 py-6 text-white">
                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-orange-300">Bus Handoff Confirmation</p>
                <h1 class="mt-2 text-2xl font-black">Confirm package receipt</h1>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-300">Only confirm if the package is physically in your hands.</p>
            </div>

            <div class="space-y-5 p-6">
                @if(session('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ session('error') }}</div>
                @endif

                @if(!$handoff)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-8 text-center">
                        <p class="text-lg font-black text-slate-900">Link unavailable</p>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">This confirmation link is invalid, expired, or already unavailable.</p>
                    </div>
                @else
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Package</p>
                                <p class="mt-1 text-lg font-black text-slate-950">{{ $handoff['package']['tracking_code'] ?? 'Package' }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-500">{{ $handoff['package']['description'] ?? '' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Bus Station</p>
                                <p class="mt-1 text-lg font-black text-slate-950">{{ $handoff['handoff']['bus_station'] ?? '-' }}</p>
                                @php($handedOffAt = !empty($handoff['handoff']['handed_off_at']) ? \Illuminate\Support\Carbon::parse($handoff['handoff']['handed_off_at'])->format('d M Y, h:i A') : '-')
                                <p class="mt-0.5 text-sm font-semibold text-slate-500">Handed off {{ $handedOffAt }}</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('bus-handoff.public.confirm', $token) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        @csrf
                        <h2 class="text-lg font-black text-emerald-950">I have received this package</h2>
                        <label class="mt-4 block">
                            <span class="mb-2 block text-xs font-black uppercase tracking-wide text-emerald-800">Comment optional</span>
                            <textarea name="notes" rows="3" class="w-full rounded-2xl border-2 border-emerald-100 bg-white px-4 py-3 text-sm font-bold text-slate-950 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="Anything we should know?"></textarea>
                        </label>
                        <button type="submit" class="mt-4 w-full rounded-2xl bg-emerald-600 px-5 py-4 text-sm font-black text-white shadow-lg shadow-emerald-600/20">Confirm Received</button>
                    </form>

                    <form method="POST" action="{{ route('bus-handoff.public.issue', $token) }}" class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        @csrf
                        <h2 class="text-lg font-black text-rose-950">I have not received it / there is an issue</h2>
                        <label class="mt-4 block">
                            <span class="mb-2 block text-xs font-black uppercase tracking-wide text-rose-800">Reason</span>
                            <select name="reason_id" required class="w-full rounded-2xl border-2 border-rose-100 bg-white px-4 py-3 text-sm font-bold text-slate-950 outline-none focus:border-rose-400 focus:ring-4 focus:ring-rose-100">
                                @foreach($reasons as $reason)
                                    <option value="{{ $reason['id'] }}">{{ $reason['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="mt-4 block">
                            <span class="mb-2 block text-xs font-black uppercase tracking-wide text-rose-800">Comment optional</span>
                            <textarea name="notes" rows="3" class="w-full rounded-2xl border-2 border-rose-100 bg-white px-4 py-3 text-sm font-bold text-slate-950 outline-none focus:border-rose-400 focus:ring-4 focus:ring-rose-100" placeholder="Tell us what happened"></textarea>
                        </label>
                        <button type="submit" class="mt-4 w-full rounded-2xl bg-rose-600 px-5 py-4 text-sm font-black text-white shadow-lg shadow-rose-600/20">Report Issue</button>
                    </form>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
