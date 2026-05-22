@extends('warehouse.layouts.app')

@section('title', 'Warehouse Dashboard')
@section('page-title', 'Dashboard')

@php
    $dashboardConfig = [
        'warehouse_name' => $warehouse->name,
    ];
    $counts = $dashboard['counts'] ?? [];
    $workQueues = $dashboard['work_queues'] ?? [];
    $workflowLanes = $dashboard['workflow_lanes'] ?? [];
    $exceptions = $dashboard['exceptions'] ?? [];
    $activity = $dashboard['activity'] ?? [];
    $paymentDashboard = $dashboard['payment_dashboard'] ?? [];
    $activityPages = collect($activity)->chunk(5)->values();
    $currentUser = Auth::guard('admin')->user();
    $actions = collect($dashboard['actions'] ?? [])->filter(fn ($action) => empty($action['permission']) || $currentUser?->hasPermission($action['permission']))->values();
    $canFindPackages = $currentUser?->hasPermission('warehouse.items.scan');
    $isPaymentOnlyDashboard = $currentUser?->hasPermission('warehouse.recipient_payments.view')
        && !$canFindPackages
        && count($workQueues) === 1
        && ($workQueues[0]['key'] ?? null) === 'payment_due';
    $toneClasses = [
        'orange' => ['icon' => 'bg-orange-50 text-orange-700 ring-orange-200', 'bar' => 'bg-orange-500', 'text' => 'text-orange-700', 'panel' => 'bg-orange-50/70 border-orange-100'],
        'slate' => ['icon' => 'bg-slate-100 text-slate-700 ring-slate-200', 'bar' => 'bg-slate-500', 'text' => 'text-slate-700', 'panel' => 'bg-slate-50 border-slate-200'],
        'violet' => ['icon' => 'bg-violet-50 text-violet-700 ring-violet-200', 'bar' => 'bg-violet-500', 'text' => 'text-violet-700', 'panel' => 'bg-violet-50/70 border-violet-100'],
        'blue' => ['icon' => 'bg-blue-50 text-blue-700 ring-blue-200', 'bar' => 'bg-blue-500', 'text' => 'text-blue-700', 'panel' => 'bg-blue-50/70 border-blue-100'],
        'cyan' => ['icon' => 'bg-cyan-50 text-cyan-700 ring-cyan-200', 'bar' => 'bg-cyan-500', 'text' => 'text-cyan-700', 'panel' => 'bg-cyan-50/70 border-cyan-100'],
        'emerald' => ['icon' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'bar' => 'bg-emerald-500', 'text' => 'text-emerald-700', 'panel' => 'bg-emerald-50/70 border-emerald-100'],
        'amber' => ['icon' => 'bg-amber-50 text-amber-700 ring-amber-200', 'bar' => 'bg-amber-500', 'text' => 'text-amber-700', 'panel' => 'bg-amber-50/70 border-amber-100'],
        'rose' => ['icon' => 'bg-rose-50 text-rose-700 ring-rose-200', 'bar' => 'bg-rose-500', 'text' => 'text-rose-700', 'panel' => 'bg-rose-50/70 border-rose-100'],
    ];
@endphp

@section('content')
<div class="space-y-5" x-data="warehouseDashboardPage" data-warehouse-dashboard-config="{{ e(json_encode($dashboardConfig)) }}">
    @unless($isPaymentOnlyDashboard)
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-600">Warehouse Operations</p>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950" x-text="warehouseName"></h1>
                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ now()->format('l, M j, Y g:i A') }}</p>
                </div>
                @if($canFindPackages)
                    <form action="{{ route('warehouse.packages.index') }}" method="GET" class="flex min-w-0 flex-1 flex-col gap-2 sm:max-w-xl sm:flex-row">
                        <div class="relative min-w-0 flex-1">
                            <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input name="search" type="search" placeholder="Search package, barcode, recipient" class="h-12 w-full rounded-xl border-2 border-slate-200 bg-white pl-11 pr-4 text-sm font-bold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        </div>
                        <button type="submit" class="h-12 rounded-xl border-2 border-slate-900 bg-slate-900 px-5 text-sm font-black text-white shadow-sm">Find</button>
                    </form>
                @endif
            </div>
            <div class="grid gap-2 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                @foreach($actions as $action)
                    <a href="{{ $action['url'] }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-center text-xs font-black text-slate-700 transition hover:border-orange-200 hover:bg-orange-50 hover:text-orange-800">
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </section>
    @endunless

    @unless($isPaymentOnlyDashboard)
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach($workQueues as $queue)
                @php $tone = $toneClasses[$queue['tone']] ?? $toneClasses['slate']; @endphp
                <a href="{{ $queue['url'] }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                    <div class="h-1.5 {{ $tone['bar'] }}"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl ring-1 {{ $tone['icon'] }}">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">{{ $queue['label'] }}</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-slate-950">{{ number_format($queue['value']) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">{{ $queue['detail'] }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </section>
    @endunless

    @if($isPaymentOnlyDashboard)
        @php $session = $paymentDashboard['session'] ?? null; @endphp
        <section class="space-y-4">
            <div class="flex items-center gap-3">
                <h2 class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Today's Work</h2>
                <div class="h-px flex-1 bg-slate-200"></div>
                <a href="{{ route('warehouse.recipient-payments.index') }}" class="inline-flex h-9 shrink-0 items-center justify-center rounded-xl border border-orange-200 bg-orange-50 px-3 text-xs font-black text-orange-700 transition hover:border-orange-300 hover:bg-orange-100">
                    Process Payments
                </a>
            </div>
            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                <a href="{{ route('warehouse.recipient-payments.index') }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                    <div class="h-1.5 bg-amber-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">My Active Payments</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-slate-950">{{ number_format((int) ($paymentDashboard['active_count'] ?? 0)) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Payments assigned to you</p>
                        </div>
                    </div>
                </a>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-emerald-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Paid Today</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-emerald-700">{{ number_format((int) ($paymentDashboard['paid_today'] ?? 0)) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Payments completed today</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-blue-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 ring-1 ring-blue-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m0-6h4v6h-4m0-6a2 2 0 100 4m0-4v4"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Recorded Today</p>
                            <p class="mt-1 whitespace-nowrap text-base font-black text-slate-950 sm:text-2xl 2xl:text-3xl">GHS {{ number_format((float) ($paymentDashboard['recorded_today'] ?? 0), 2) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">{{ number_format((int) ($paymentDashboard['recorded_today_count'] ?? 0)) }} payment records</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-violet-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700 ring-1 ring-violet-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2 5a2 2 0 012-2h3.3a1 1 0 01.95.68l1.1 3.3a1 1 0 01-.24 1.02L7.2 9.9a13 13 0 006.9 6.9l1.9-1.9a1 1 0 011.02-.24l3.3 1.1a1 1 0 01.68.95V20a2 2 0 01-2 2h-1C9.2 22 2 14.8 2 6V5z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Calls Today</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-slate-950">{{ number_format((int) ($paymentDashboard['calls_today'] ?? 0)) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Recipient follow-up calls</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 space-y-4 sm:mt-10">
            <div class="flex items-center gap-3">
                <h2 class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Payment Session</h2>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 {{ ($session['status'] ?? null) === 'open' ? 'bg-emerald-500' : 'bg-slate-400' }}"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl {{ ($session['status'] ?? null) === 'open' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }} ring-1">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Session Status</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-slate-950">{{ $session ? str($session['status'])->replace('_', ' ')->title() : 'None' }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">{{ $session ? collect([$session['wallet'] ?? null, $session['wallet_phone'] ?? null])->filter()->join(' / ') : 'Start a session first' }}</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-slate-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-2.2 0-4 .9-4 2s1.8 2 4 2 4 .9 4 2-1.8 2-4 2m0-8V6m0 10v2"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Opening Balance</p>
                            <p class="mt-1 whitespace-nowrap text-base font-black text-slate-950 sm:text-2xl 2xl:text-3xl">GHS {{ number_format((float) ($session['opening_balance'] ?? 0), 2) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Started {{ $session['started_at'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-orange-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-700 ring-1 ring-orange-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16M7 16l3-3 3 2 4-6M17 9h-4m4 0v4"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Expected Balance</p>
                            <p class="mt-1 whitespace-nowrap text-base font-black text-slate-950 sm:text-2xl 2xl:text-3xl">GHS {{ number_format((float) ($session['expected_closing_balance'] ?? 0), 2) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Opening + payments</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-slate-900"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white ring-1 ring-slate-800">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Closing Balance</p>
                            <p class="mt-1 whitespace-nowrap text-base font-black text-slate-950 sm:text-2xl 2xl:text-3xl">{{ ($session['closing_balance'] ?? null) === null ? '-' : 'GHS ' . number_format((float) $session['closing_balance'], 2) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">{{ ($session['closed_at'] ?? null) ? 'Closed ' . $session['closed_at'] : 'Not closed yet' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 space-y-4 sm:mt-10">
            <div class="flex items-center gap-3">
                <h2 class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Call Outcomes</h2>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>
            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-emerald-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3l2 5-2 1a12 12 0 006 6l1-2 5 2v3a2 2 0 01-2 2h-1C9.3 20 4 14.7 4 7V5z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Answered</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-slate-950">{{ number_format((int) data_get($paymentDashboard, 'call_counts.answered', 0)) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Calls picked up</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-slate-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.4 5.6l-12.8 12.8M8 5H5a2 2 0 00-2 2v1c0 7.7 6.3 14 14 14h1a2 2 0 002-2v-3"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">No Answer</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-slate-950">{{ number_format((int) data_get($paymentDashboard, 'call_counts.no_answer', 0)) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Try again later</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-amber-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Pay Later</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-amber-700">{{ number_format((int) data_get($paymentDashboard, 'call_counts.pay_later', 0)) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Promised for later</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-1.5 bg-rose-500"></div>
                    <div class="flex items-start gap-3 p-3 sm:gap-4 sm:p-5">
                        <div class="flex h-9 w-9 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Wrong / Busy</p>
                            <p class="mt-1 text-2xl font-black sm:text-3xl text-slate-950">{{ number_format((int) data_get($paymentDashboard, 'call_counts.wrong_number', 0) + (int) data_get($paymentDashboard, 'call_counts.busy', 0)) }}</p>
                            <p class="mt-2 text-xs font-bold leading-5 text-slate-500">Needs follow-up decision</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @unless($isPaymentOnlyDashboard)
    <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_390px]">
        <div class="space-y-5">
            @unless($isPaymentOnlyDashboard)
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">What Needs Action</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @php
                            $nextActions = collect($workQueues)->filter(fn ($queue) => (int) $queue['value'] > 0)->take(6);
                        @endphp
                        @forelse($nextActions as $queue)
                            @php $tone = $toneClasses[$queue['tone']] ?? $toneClasses['slate']; @endphp
                            <a href="{{ $queue['url'] }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-slate-900">{{ $queue['label'] }}</p>
                                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $queue['detail'] }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="text-xl font-black {{ $tone['text'] }}">{{ number_format($queue['value']) }}</span>
                                    <span class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700">Open</span>
                                </div>
                            </a>
                        @empty
                            <div class="px-5 py-8 text-center text-sm font-bold text-slate-400">No urgent warehouse queues right now.</div>
                        @endforelse
                    </div>
                </section>
            @endunless

            @unless($isPaymentOnlyDashboard)
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Workflow Lanes</h2>
                    </div>
                    <div class="grid gap-3 p-3 sm:gap-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
                        @foreach($workflowLanes as $lane)
                            <div class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <h3 class="text-sm font-black text-slate-900">{{ $lane['label'] }}</h3>
                                <p class="mt-1 min-h-10 text-xs font-bold leading-5 text-slate-500">{{ $lane['detail'] }}</p>
                                <div class="mt-4 grid gap-2">
                                    @foreach($lane['items'] as $item)
                                        <a href="{{ $item['url'] }}" class="flex min-h-14 items-center justify-between gap-3 rounded-xl bg-white px-3 py-3 text-xs font-black leading-5 text-slate-700 ring-1 ring-slate-200 transition hover:ring-orange-200 hover:text-orange-800">
                                            <span class="min-w-0 break-words">{{ $item['label'] }}</span>
                                            @if($item['value'] !== null)
                                                <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-[11px] text-slate-700">{{ number_format($item['value']) }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endunless
        </div>

        <aside class="space-y-5">
            @unless($isPaymentOnlyDashboard)
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Exception Center</h2>
                </div>
                <div class="grid gap-3 p-5">
                    @foreach($exceptions as $exception)
                        <a href="{{ $exception['url'] }}" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-rose-200 hover:bg-rose-50/50">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-slate-900">{{ $exception['label'] }}</p>
                                    <p class="mt-1 text-xs font-bold leading-5 text-slate-500">{{ $exception['detail'] }}</p>
                                </div>
                                <span class="rounded-xl bg-rose-50 px-3 py-2 text-sm font-black text-rose-700 ring-1 ring-rose-100">{{ number_format($exception['value']) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
            @endunless

            <section x-data="{ page: 1, total: {{ max($activityPages->count(), 1) }} }" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-wide text-slate-700">Recent Activity</h2>
                    @if($activityPages->count() > 1)
                        <span class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-600">
                            <span x-text="page"></span> / {{ $activityPages->count() }}
                        </span>
                    @endif
                </div>
                <div class="divide-y divide-slate-100">
                    @if($activityPages->isNotEmpty())
                        @foreach($activityPages as $pageIndex => $events)
                            <div x-show="page === {{ $pageIndex + 1 }}" x-cloak>
                                @foreach($events as $event)
                                    @php $tone = $toneClasses[$event['tone']] ?? $toneClasses['slate']; @endphp
                                    <a href="{{ $event['url'] }}" class="flex gap-3 px-5 py-4 transition hover:bg-slate-50">
                                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $tone['bar'] }} ring-4 ring-slate-100"></span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black text-slate-900">{{ $event['label'] }}</p>
                                            <p class="mt-1 truncate text-xs font-bold text-slate-500">{{ $event['detail'] ?: '-' }}</p>
                                            <p class="mt-1 text-[11px] font-semibold text-slate-400">{{ collect([$event['actor'], $event['time']])->filter()->join(' / ') }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <div class="px-5 py-8 text-center text-sm font-bold text-slate-400">No recent activity yet.</div>
                    @endif
                </div>
                @if($activityPages->count() > 1)
                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
                        <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page <= 1" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Prev</button>
                        <p class="text-xs font-black text-slate-500">Showing 5 per page</p>
                        <button type="button" @click="page = Math.min(total, page + 1)" :disabled="page >= total" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Next</button>
                    </div>
                @endif
            </section>
        </aside>
    </section>
    @endunless
</div>
@endsection
