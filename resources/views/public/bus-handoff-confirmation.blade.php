<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Confirm Delivery</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $trackingCode = $handoff['package']['tracking_code'] ?? 'Package';
    $description = $handoff['package']['description'] ?? 'Parcelman package';
    $station = $handoff['handoff']['bus_station'] ?? '-';
    $handedOffAt = !empty($handoff['handoff']['handed_off_at'])
        ? \Illuminate\Support\Carbon::parse($handoff['handoff']['handed_off_at'])->format('d M Y, h:i A')
        : '-';
@endphp
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
                        <h1 class="mt-1 text-2xl font-black leading-tight sm:text-3xl">Confirm package receipt</h1>
                        <p class="mt-1 max-w-xl text-sm font-semibold leading-5 text-orange-100/80">Only confirm when the package is physically in your hands. If anything is wrong, report it instead.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                @if(session('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ session('error') }}</div>
                @endif

                @if(!$handoff)
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-12 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                        </div>
                        <p class="mt-5 text-xl font-black text-slate-900">Link unavailable</p>
                        <p class="mx-auto mt-2 max-w-sm text-sm font-semibold leading-6 text-slate-500">This confirmation link is invalid, expired, or already unavailable.</p>
                    </div>
                @else
                    <div class="border-b border-slate-200 pb-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-700">Package to confirm</p>
                                <p class="mt-1 break-words font-mono text-2xl font-black tracking-tight text-slate-950">{{ $trackingCode }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $description }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="text-sm font-black text-slate-950">{{ $station }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-500">Handed off {{ $handedOffAt }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button type="button" data-open-modal="received-modal" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-orange-600 to-orange-500 px-5 py-4 text-base font-black text-white shadow-lg shadow-orange-600/25 transition hover:from-orange-700 hover:to-orange-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                            I have received this package
                        </button>

                        <button type="button" data-open-modal="issue-modal" class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-slate-200 bg-white px-5 py-4 text-base font-black text-slate-800 transition hover:border-orange-300 hover:bg-orange-50">
                            <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                            I have not received it
                        </button>
                    </div>

                    <p class="text-center text-xs font-semibold leading-5 text-slate-500">
                        This page is for confirming bus-station handoff delivery. Do not confirm for someone else or before receiving the package.
                    </p>

                    <div id="received-modal" class="fixed inset-0 z-50 hidden min-h-dvh w-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4" role="dialog" aria-modal="true">
                        <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-950/25">
                            <div class="border-b border-slate-100 px-5 py-5">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-xl font-black text-slate-950">Confirm received</h2>
                                        <p class="mt-1 text-sm font-semibold leading-5 text-slate-500">Submit this only if the package is physically with you.</p>
                                    </div>
                                    <button type="button" data-close-modal class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 hover:bg-slate-200">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('bus-handoff.public.confirm', $token) }}" class="space-y-4 px-5 py-5">
                                @csrf
                                <label class="block">
                                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Comment optional</span>
                                    <textarea name="notes" rows="4" class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Anything we should know?"></textarea>
                                </label>
                                <div class="flex justify-end gap-3">
                                    <button type="button" data-close-modal class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600">Cancel</button>
                                    <button type="submit" class="rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-600/20">Confirm</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="issue-modal" class="fixed inset-0 z-50 hidden min-h-dvh w-screen items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm sm:p-4" role="dialog" aria-modal="true">
                        <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-950/25">
                            <div class="border-b border-slate-100 px-5 py-5">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-700">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-xl font-black text-slate-950">Report an issue</h2>
                                        <p class="mt-1 text-sm font-semibold leading-5 text-slate-500">Choose the reason and add a short note if needed.</p>
                                    </div>
                                    <button type="button" data-close-modal class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 hover:bg-slate-200">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('bus-handoff.public.issue', $token) }}" class="space-y-4 px-5 py-5">
                                @csrf
                                <label class="block">
                                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Reason</span>
                                    <select name="reason_id" required class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                        @foreach($reasons as $reason)
                                            <option value="{{ $reason['id'] }}">{{ $reason['label'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Comment optional</span>
                                    <textarea name="notes" rows="4" class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Tell us what happened"></textarea>
                                </label>
                                <div class="flex justify-end gap-3">
                                    <button type="button" data-close-modal class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600">Cancel</button>
                                    <button type="submit" class="rounded-2xl bg-rose-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-rose-600/20">Report</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-open-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.openModal);
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', () => closeModal(button.closest('[role="dialog"]')));
        });

        document.querySelectorAll('[role="dialog"]').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal(modal);
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('[role="dialog"].flex').forEach(closeModal);
        });

        function closeModal(modal) {
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (!document.querySelector('[role="dialog"].flex')) {
                document.body.style.overflow = '';
            }
        }
    </script>
</body>
</html>
