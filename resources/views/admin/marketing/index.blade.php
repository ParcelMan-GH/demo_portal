@extends('admin.layouts.app')

@section('title', 'Marketing Broadcasts')
@section('breadcrumb-parent', 'System')
@section('breadcrumb-current', 'Marketing')

@php
    $channelStats = [
        [
            'key' => 'total',
            'label' => 'Broadcasts',
            'value' => $totalBroadcasts,
            'detail' => 'All channel sends',
            'tone' => 'slate',
            'icon' => 'megaphone',
        ],
        [
            'key' => 'push',
            'label' => 'Push Sent',
            'value' => $broadcastsByChannel['push'] ?? 0,
            'detail' => number_format($vendorCountPush + $driverCountPush) . ' reachable',
            'tone' => 'amber',
            'icon' => 'bell',
        ],
        [
            'key' => 'sms',
            'label' => 'SMS Sent',
            'value' => $broadcastsByChannel['sms'] ?? 0,
            'detail' => number_format($vendorCountSms + $driverCountSms) . ' reachable',
            'tone' => 'emerald',
            'icon' => 'chat',
        ],
        [
            'key' => 'email',
            'label' => 'Email Sent',
            'value' => $broadcastsByChannel['email'] ?? 0,
            'detail' => number_format($vendorCountEmail + $driverCountEmail) . ' reachable',
            'tone' => 'blue',
            'icon' => 'mail',
        ],
    ];
@endphp

@section('content')
<div class="space-y-5" x-data="marketingPage()">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-sm">
        <div class="relative px-4 py-5 sm:px-6">
            <div class="pointer-events-none absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.22),transparent_58%)]"></div>
            <div class="relative flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0 max-w-xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-orange-200">
                        Customer Messaging
                    </div>
                    <h1 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">Marketing Broadcasts</h1>
                    <p class="mt-2 text-sm font-medium leading-6 text-slate-300">Send push notifications, SMS, and email campaigns to active vendors and riders.</p>
                </div>
                <div class="grid w-full grid-cols-2 gap-2 sm:grid-cols-4 xl:mt-11 xl:max-w-[680px]">
                    @foreach($channelStats as $stat)
                        <button type="button" x-data="{ key: @js($stat['key']) }" @@click="filterByStat(key)" class="group flex min-w-0 cursor-pointer items-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-2.5 py-3 text-left transition hover:border-orange-300/30 hover:bg-white/15 focus:outline-none focus:ring-4 focus:ring-orange-300/20">
                            <div @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ring-1',
                                'bg-white/10 text-slate-200 ring-white/15' => $stat['tone'] === 'slate',
                                'bg-amber-400/10 text-amber-200 ring-amber-300/20' => $stat['tone'] === 'amber',
                                'bg-emerald-400/10 text-emerald-200 ring-emerald-300/20' => $stat['tone'] === 'emerald',
                                'bg-blue-400/10 text-blue-200 ring-blue-300/20' => $stat['tone'] === 'blue',
                            ])>
                                @if($stat['icon'] === 'megaphone')
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M11 5.5v13a1.6 1.6 0 01-3.1.54L6.2 14H5a3 3 0 010-6h1.2C8.5 8 10.2 7.2 11 5.5zm0 0c2.9 0 5.3-1 7-2.5v18c-1.7-1.5-4.1-2.5-7-2.5M18 9a3 3 0 010 6"/></svg>
                                @elseif($stat['icon'] === 'bell')
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                                @elseif($stat['icon'] === 'chat')
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.4-4 8-9 8-1.5 0-2.9-.3-4.2-.9L3 20l1.4-3.7A7.4 7.4 0 013 12c0-4.4 4-8 9-8s9 3.6 9 8z"/></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 8l8 5.2a2 2 0 002.1 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-[9px] font-black uppercase leading-snug tracking-wide text-slate-400">{{ $stat['label'] }}</p>
                                <p class="mt-0.5 text-lg font-extrabold text-white">{{ number_format($stat['value']) }}</p>
                                <p class="mt-0.5 truncate text-[10px] font-semibold text-slate-300">{{ $stat['detail'] }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @@click="activeTab = 'compose'" class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition" :class="activeTab === 'compose' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Compose
            </button>
            <button type="button" @@click="activeTab = 'recent'" class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition" :class="activeTab === 'recent' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Broadcast Log
                <span class="rounded-full px-2 py-0.5 text-[10px] font-black" :class="activeTab === 'recent' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'" x-text="tableMeta.total || 0"></span>
            </button>
        </div>
    </section>

    <div class="space-y-5">
    <div x-show="activeTab === 'compose'" x-cloak class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-extrabold text-slate-900">Compose Broadcast</h2>
                        <p class="truncate text-sm text-slate-500">Build the message, preview it, then confirm before sending.</p>
                    </div>
                </div>
                <div class="flex overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-1">
                    <template x-for="channel in channelOptions" :key="channel.value">
                        <button type="button" @@click="setChannel(channel.value)" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-black transition sm:text-sm" :class="form.channel === channel.value ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white'">
                            <span x-text="channel.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="grid gap-0 xl:grid-cols-[minmax(0,1fr)_420px]">
            <form @@submit.prevent="openConfirm()" class="space-y-5 p-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Audience</label>
                        <select x-model="form.audience" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="all_vendors" x-text="`All Vendors (${audienceCount('vendors')} reachable)`"></option>
                            <option value="all_drivers" x-text="`All Riders (${audienceCount('drivers')} reachable)`"></option>
                            <option value="all" x-text="`Everyone (${audienceCount('vendors') + audienceCount('drivers')} reachable)`"></option>
                        </select>
                    </div>
                    <div x-show="form.channel === 'email'" x-transition>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Email Template</label>
                        <select x-model="form.email_template" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="custom">Custom Message</option>
                            <template x-for="tpl in emailTemplates" :key="tpl.name">
                                <option :value="tpl.name" x-text="tpl.name"></option>
                            </template>
                        </select>
                    </div>
                    <div x-show="form.channel === 'push'" x-transition>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Notification Title <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="form.title" maxlength="200" placeholder="Short push title" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div x-show="form.channel === 'email'" x-transition>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Email Subject <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="form.subject" maxlength="200" placeholder="Email subject" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-xs font-extrabold uppercase tracking-wide text-slate-600">
                            <span x-text="messageLabel()"></span> <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] font-bold text-slate-400" x-text="messageCounter()"></span>
                    </div>
                    <textarea x-model="form.body" :rows="form.channel === 'sms' ? 4 : 7" placeholder="Write the message recipients will receive..." class="w-full resize-none rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-sm font-semibold leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100"></textarea>
                </div>

                <template x-if="result">
                    <div class="rounded-2xl border p-4 text-sm font-semibold" :class="result.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl" :class="result.success ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                <svg x-show="result.success" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="!result.success" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                            </div>
                            <div>
                                <p class="font-black" x-text="result.success ? 'Broadcast sent' : 'Broadcast failed'"></p>
                                <p class="mt-1 text-xs opacity-80" x-text="result.message"></p>
                                <div x-show="result.success && result.data" class="mt-2 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-white/70 px-2 py-1" x-text="`${result.data?.sent || 0} delivered`"></span>
                                    <span class="rounded-full bg-white/70 px-2 py-1" x-show="(result.data?.failed || 0) > 0" x-text="`${result.data?.failed || 0} failed`"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" @@click="resetForm(true)" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50">Reset</button>
                    <button type="submit" :disabled="form.submitting || !canSend()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg x-show="form.submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path></svg>
                        <svg x-show="!form.submitting" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span x-text="form.submitting ? 'Sending...' : 'Review & Send'"></span>
                    </button>
                </div>
            </form>

            <aside class="border-t border-slate-100 bg-slate-50/70 p-5 xl:border-l xl:border-t-0">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-600">Live Preview</p>
                    <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-black uppercase text-slate-500 ring-1 ring-slate-200" x-text="audienceLabel()"></span>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div x-show="form.channel === 'push'" x-transition>
                        <div class="rounded-2xl border border-slate-200 bg-slate-950 p-3">
                            <div class="mb-3 flex items-center justify-between text-[10px] font-bold text-white/80"><span>9:41 AM</span><span>Parcelman</span></div>
                            <div class="rounded-2xl bg-white p-3 shadow-lg">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-black text-slate-900" x-text="form.title || 'Notification title'"></p>
                                        <p class="mt-1 line-clamp-4 whitespace-pre-wrap text-xs font-medium text-slate-600" x-text="form.body || 'Your push message will appear here.'"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="form.channel === 'sms'" x-transition>
                        <div class="rounded-2xl border border-slate-200 bg-slate-100 p-3">
                            <div class="mb-3 text-xs font-black text-slate-700">Messages</div>
                            <div class="min-h-40 rounded-2xl bg-white p-4">
                                <div class="max-w-[86%] rounded-2xl rounded-bl-md border border-slate-100 bg-slate-50 px-3 py-2 shadow-sm">
                                    <p class="whitespace-pre-wrap text-xs font-medium leading-5 text-slate-800" x-text="form.body || 'Your SMS message will appear here.'"></p>
                                    <p class="mt-1 text-right text-[10px] font-semibold text-slate-400">now</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="form.channel === 'email'" x-transition>
                        <div class="rounded-2xl border border-slate-200 bg-white">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">From</p>
                                <p class="text-sm font-bold text-slate-900">{{ config('mail.from.name', 'Parcelman') }}</p>
                            </div>
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Subject</p>
                                <p class="truncate text-sm font-bold text-slate-900" x-text="form.subject || 'Email subject'"></p>
                            </div>
                            <div class="min-h-48 px-4 py-4">
                                <div x-show="form.email_template !== 'custom'" class="mb-3 rounded-xl bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700" x-text="`Template: ${form.email_template}`"></div>
                                <p class="whitespace-pre-wrap text-sm font-medium leading-6 text-slate-700" x-text="form.body || 'Your email body will appear here.'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <div x-show="activeTab === 'recent'" x-cloak class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-extrabold text-slate-900">Broadcast Log</h2>
                        <p class="truncate text-sm text-slate-500">Search every broadcast and filter by channel, recipient, status, date, audience, or template.</p>
                    </div>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700" x-text="`${tableMeta.total || 0} records`"></span>
            </div>
        </div>

        <div class="border-b border-slate-100 px-5 py-4">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="w-full xl:max-w-md">
                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="filters.search" @@input.debounce.500ms="applyFilters()" placeholder="Search broadcast log" class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-base font-semibold text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-orange-400 focus:ring-4 focus:ring-orange-100 sm:text-sm">
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <button type="button" @@click="showFilters = !showFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50" :class="showFilters ? 'border-orange-200 bg-orange-50 text-orange-700 ring-1 ring-orange-100' : ''">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z"/></svg>
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'"></span>
                    </button>
                    <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">Clear</button>
                </div>
            </div>

            <div x-show="showFilters" x-transition class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4" style="display:none">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Sent Date</label>
                        <input type="text" x-ref="sentDateRange" placeholder="Select date range" readonly class="w-full cursor-pointer rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Channel</label>
                        <select x-model="filters.channel" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All channels</option>
                            <option value="push">Push</option>
                            <option value="sms">SMS</option>
                            <option value="email">Email</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Status</label>
                        <select x-model="filters.status" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All statuses</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Audience</label>
                        <select x-model="filters.audience" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All audiences</option>
                            <option value="all_vendors">All Vendors</option>
                            <option value="all_drivers">All Riders</option>
                            <option value="all">Vendors & Riders</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Email Template</label>
                        <select x-model="filters.template" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All templates</option>
                            <option value="custom">Custom</option>
                            <template x-for="tpl in emailTemplates" :key="tpl.name">
                                <option :value="tpl.name" x-text="tpl.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-600">Error State</label>
                        <select x-model="filters.has_error" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-900 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                            <option value="">All records</option>
                            <option value="1">Has error</option>
                            <option value="0">No error</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" @@click="showFilters = false" class="mr-auto rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Close Filters</button>
                    <button type="button" @@click="clearFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Clear Filters</button>
                    <button type="button" @@click="applyFilters()" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">Apply Filters</button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2" x-show="activeFilterChips().length">
                <template x-for="chip in activeFilterChips()" :key="chip.key">
                    <span class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold text-orange-700 ring-1 ring-orange-200">
                        <span x-text="chip.label"></span>
                        <button type="button" @@click="clearFilter(chip.key)" class="text-orange-500 hover:text-orange-800">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="tableLoading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 z-10 bg-white/60 backdrop-blur-[1px]"></div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full table-auto divide-y divide-slate-200/50 text-xs">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Sent At</th>
                            <th class="whitespace-nowrap px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Channel</th>
                            <th class="whitespace-nowrap px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Message</th>
                            <th class="whitespace-nowrap px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Recipient</th>
                            <th class="whitespace-nowrap px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">Audience</th>
                            <th class="whitespace-nowrap px-4 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/50 bg-transparent">
                        <template x-if="!tableLoading && broadcasts.length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M11 5.5v13a1.6 1.6 0 01-3.1.54L6.2 14H5a3 3 0 010-6h1.2C8.5 8 10.2 7.2 11 5.5zm0 0c2.9 0 5.3-1 7-2.5v18c-1.7-1.5-4.1-2.5-7-2.5"/></svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">No broadcasts match the current filters</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="log in broadcasts" :key="log.id">
                            <tr class="hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="font-semibold text-slate-700" x-text="formatDate(log.created_at)"></p>
                                    <p class="text-[11px] font-medium text-slate-400" x-text="formatTime(log.created_at)"></p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-black uppercase" :class="channelBadgeClass(log.channel)" x-text="log.channel || '-'"></span>
                                </td>
                                <td class="max-w-[460px] px-4 py-3">
                                    <p class="truncate text-sm font-bold text-slate-900" x-text="log.title || '-'"></p>
                                    <p class="mt-0.5 line-clamp-2 text-xs font-medium leading-5 text-slate-500" x-text="log.body || '-'"></p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="text-xs font-black text-slate-900" x-text="log.recipient_name || '-'"></p>
                                    <p class="mt-0.5 text-[11px] font-semibold text-slate-500" x-text="log.recipient_phone || log.recipient_email || log.notifiable_type || '-'"></p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <p class="text-xs font-bold text-slate-700" x-text="audienceText(log.audience)"></p>
                                    <p class="mt-0.5 text-[11px] font-semibold text-slate-400" x-show="log.template" x-text="`Template: ${log.template}`"></p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-black" :class="statusBadgeClass(log.status)" x-text="statusLabel(log.status)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                <template x-if="!tableLoading && broadcasts.length === 0">
                    <div class="px-4 py-12 text-center text-sm font-semibold text-slate-400">No broadcasts match the current filters.</div>
                </template>
                <template x-for="log in broadcasts" :key="log.id">
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-extrabold text-slate-900" x-text="log.title || 'Broadcast'"></p>
                                <p class="mt-1 text-xs font-semibold text-slate-500" x-text="`${formatDate(log.created_at)} ${formatTime(log.created_at)}`"></p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-black uppercase" :class="channelBadgeClass(log.channel)" x-text="log.channel || '-'"></span>
                        </div>
                        <p class="mt-3 line-clamp-3 text-sm font-medium leading-6 text-slate-600" x-text="log.body || '-'"></p>
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600" x-text="log.recipient_name || log.notifiable_type || '-'"></span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600" x-text="audienceText(log.audience)"></span>
                            <span class="rounded-full border px-2.5 py-1 text-[10px] font-black" :class="statusBadgeClass(log.status)" x-text="statusLabel(log.status)"></span>
                        </div>
                    </article>
                </template>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-semibold text-slate-600">
                        Showing <span x-text="tableMeta.from || 0"></span> to <span x-text="tableMeta.to || 0"></span> of <span x-text="tableMeta.total || 0"></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button @@click="goToPage(tableMeta.current_page - 1)" :disabled="tableMeta.current_page <= 1" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="px-2 text-xs font-black text-slate-700">Page <span x-text="tableMeta.current_page || 1"></span> / <span x-text="tableMeta.last_page || 1"></span></div>
                        <button @@click="goToPage(tableMeta.current_page + 1)" :disabled="tableMeta.current_page >= tableMeta.last_page" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="confirmOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[220] flex min-h-screen items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" style="display:none">
            <div @@click.stop x-transition.scale.origin.center class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">Send Broadcast</h3>
                            <p class="mt-1 text-sm font-medium text-slate-500">Confirm the channel and audience before sending.</p>
                        </div>
                    </div>
                    <button type="button" @@click="confirmOpen = false" class="rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:text-slate-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 p-5">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Channel</p>
                            <p class="mt-1 text-sm font-black uppercase text-slate-900" x-text="form.channel"></p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Reachable</p>
                            <p class="mt-1 text-sm font-black text-slate-900" x-text="audienceReachable()"></p>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-400" x-text="form.channel === 'email' ? 'Subject / Message' : 'Message'"></p>
                        <p x-show="form.channel === 'push'" class="mt-2 text-sm font-black text-slate-900" x-text="form.title"></p>
                        <p x-show="form.channel === 'email'" class="mt-2 text-sm font-black text-slate-900" x-text="form.subject"></p>
                        <p class="mt-2 line-clamp-5 whitespace-pre-wrap text-sm font-medium leading-6 text-slate-600" x-text="form.body"></p>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                    <button type="button" @@click="confirmOpen = false" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50">Cancel</button>
                    <button type="button" @@click="send()" :disabled="form.submitting" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:opacity-50">
                        <svg x-show="form.submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path></svg>
                        <span x-text="form.submitting ? 'Sending...' : 'Send Now'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function marketingPage() {
    return {
        activeTab: 'compose',
        channelOptions: [
            { value: 'push', label: 'Push' },
            { value: 'sms', label: 'SMS' },
            { value: 'email', label: 'Email' },
        ],
        form: {
            channel: 'push',
            title: '',
            body: '',
            subject: '',
            audience: 'all_vendors',
            email_template: 'custom',
            submitting: false,
        },
        result: null,
        confirmOpen: false,
        showFilters: false,
        counts: {
            push:  { vendors: {{ $vendorCountPush }},  drivers: {{ $driverCountPush }} },
            sms:   { vendors: {{ $vendorCountSms }},   drivers: {{ $driverCountSms }} },
            email: { vendors: {{ $vendorCountEmail }},  drivers: {{ $driverCountEmail }} },
        },
        emailTemplates: @json($emailTemplates),
        broadcasts: [],
        tableLoading: false,
        tableMeta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        filters: {
            search: '',
            date_from: '',
            date_to: '',
            channel: '',
            status: '',
            audience: '',
            template: '',
            has_error: '',
        },

        init() {
            this.loadBroadcasts();
            this.initDateRange();
        },

        setChannel(channel) {
            this.form.channel = channel;
            this.result = null;
        },

        audienceCount(type) {
            return this.counts[this.form.channel]?.[type] || 0;
        },

        audienceReachable() {
            if (this.form.audience === 'all_vendors') return `${this.audienceCount('vendors')} vendors`;
            if (this.form.audience === 'all_drivers') return `${this.audienceCount('drivers')} drivers`;
            return `${this.audienceCount('vendors') + this.audienceCount('drivers')} people`;
        },

        audienceLabel() {
            const labels = { all_vendors: 'All Vendors', all_drivers: 'All Riders', all: 'Vendors & Riders' };
            return labels[this.form.audience] || this.form.audience;
        },

        smsSegments() {
            return Math.ceil(this.form.body.length / 160) || 1;
        },

        messageLabel() {
            if (this.form.channel === 'sms') return 'SMS Message';
            if (this.form.channel === 'email') return this.form.email_template === 'custom' ? 'Email Body' : 'Template Content';
            return 'Push Message';
        },

        messageCounter() {
            if (this.form.channel === 'sms') return `${this.form.body.length}/160 (${this.smsSegments()} segment${this.smsSegments() > 1 ? 's' : ''})`;
            return `${this.form.body.length}/5000`;
        },

        canSend() {
            if (!this.form.body.trim()) return false;
            if (this.form.channel === 'push' && !this.form.title.trim()) return false;
            if (this.form.channel === 'email' && !this.form.subject.trim()) return false;
            return true;
        },

        openConfirm() {
            if (!this.canSend()) return;
            this.confirmOpen = true;
        },

        resetForm(clearResult = false) {
            this.form.title = '';
            this.form.body = '';
            this.form.subject = '';
            this.form.email_template = 'custom';
            if (clearResult) this.result = null;
        },

        async send() {
            this.form.submitting = true;
            this.result = null;

            try {
                const resp = await fetch('{{ route("admin.marketing.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        channel: this.form.channel,
                        title: this.form.title,
                        body: this.form.body,
                        subject: this.form.subject,
                        audience: this.form.audience,
                        email_template: this.form.email_template,
                    }),
                });

                const data = await resp.json();

                if (!resp.ok) {
                    this.result = { success: false, message: data.message || 'Validation failed.' };
                    this.confirmOpen = false;
                    return;
                }

                this.result = data;
                this.confirmOpen = false;

                if (data.success) {
                    this.resetForm(false);
                    this.loadBroadcasts();
                }
            } catch (e) {
                this.result = { success: false, message: 'Request failed. Please try again.' };
                this.confirmOpen = false;
            } finally {
                this.form.submitting = false;
            }
        },

        async loadBroadcasts() {
            this.tableLoading = true;

            const params = new URLSearchParams({
                page: this.tableMeta.current_page,
                per_page: this.tableMeta.per_page,
            });

            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.channel) params.set('channel', this.filters.channel);
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.date_from) params.set('date_from', this.filters.date_from);
            if (this.filters.date_to) params.set('date_to', this.filters.date_to);
            if (this.filters.audience) params.set('audience', this.filters.audience);
            if (this.filters.template) params.set('template', this.filters.template);
            if (this.filters.has_error !== '') params.set('has_error', this.filters.has_error);

            try {
                const resp = await fetch('{{ route("admin.marketing.data") }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!resp.ok) throw new Error('Failed to fetch broadcasts.');

                const json = await resp.json();
                this.broadcasts = json.data || [];
                this.tableMeta = json.meta || this.tableMeta;
            } catch (e) {
                console.error('Failed to load broadcasts:', e);
            } finally {
                this.tableLoading = false;
            }
        },

        applyFilters() {
            this.tableMeta.current_page = 1;
            this.loadBroadcasts();
        },

        clearFilters() {
            Object.keys(this.filters).forEach((key) => this.filters[key] = '');
            if (this.$refs.sentDateRange) this.$refs.sentDateRange.value = '';
            this.applyFilters();
        },

        filterByStat(key) {
            this.filters.search = '';
            this.filters.status = '';
            this.filters.audience = '';
            this.filters.template = '';
            this.filters.has_error = '';
            this.filters.date_from = '';
            this.filters.date_to = '';
            if (this.$refs.sentDateRange) this.$refs.sentDateRange.value = '';
            this.filters.channel = key === 'total' ? '' : key;
            this.activeTab = 'recent';
            this.applyFilters();
        },

        activeFilterChips() {
            const chips = [];
            if (this.filters.search) chips.push({ key: 'search', label: `Search: ${this.filters.search}` });
            if (this.filters.channel) chips.push({ key: 'channel', label: `Channel: ${this.filters.channel.toUpperCase()}` });
            if (this.filters.status) chips.push({ key: 'status', label: `Status: ${this.statusLabel(this.filters.status)}` });
            if (this.filters.date_from) chips.push({ key: 'date_from', label: `From: ${this.filters.date_from}` });
            if (this.filters.date_to) chips.push({ key: 'date_to', label: `To: ${this.filters.date_to}` });
            if (this.filters.audience) chips.push({ key: 'audience', label: `Audience: ${this.audienceText(this.filters.audience)}` });
            if (this.filters.template) chips.push({ key: 'template', label: `Template: ${this.filters.template}` });
            if (this.filters.has_error !== '') chips.push({ key: 'has_error', label: this.filters.has_error === '1' ? 'Has error' : 'No error' });
            return chips;
        },

        clearFilter(key) {
            this.filters[key] = '';
            if ((key === 'date_from' || key === 'date_to') && this.$refs.sentDateRange) this.$refs.sentDateRange.value = '';
            this.applyFilters();
        },

        initDateRange() {
            const setupPicker = () => {
                if (!this.$refs.sentDateRange || !window.$ || !window.moment || !window.$.fn.daterangepicker) return;
                const $input = window.$(this.$refs.sentDateRange);
                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'left',
                    locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                    ranges: {
                        Today: [window.moment(), window.moment()],
                        Yesterday: [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                        'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                        'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                        'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                    },
                });
                $input.on('apply.daterangepicker', (ev, picker) => {
                    this.filters.date_from = picker.startDate.format('YYYY-MM-DD');
                    this.filters.date_to = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.filters.date_from} - ${this.filters.date_to}`);
                });
                $input.on('cancel.daterangepicker', () => {
                    this.filters.date_from = '';
                    this.filters.date_to = '';
                    $input.val('');
                });
            };
            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }
            const cssId = 'daterangepicker-css';
            if (!document.getElementById(cssId)) {
                const link = document.createElement('link');
                link.id = cssId;
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                document.head.appendChild(link);
            }
            const loadScript = (id, src) => new Promise((resolve) => {
                if (document.getElementById(id)) return resolve();
                const script = document.createElement('script');
                script.id = id;
                script.src = src;
                script.onload = () => resolve();
                document.body.appendChild(script);
            });
            loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                .then(() => {
                    window.$ = window.jQuery = window.jQuery || window.$;
                    window.moment = window.moment || moment;
                    return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
                })
                .then(setupPicker);
        },

        goToPage(page) {
            if (page < 1 || page > this.tableMeta.last_page) return;
            this.tableMeta.current_page = page;
            this.loadBroadcasts();
        },

        channelBadgeClass(channel) {
            return {
                push: 'border-amber-200 bg-amber-50 text-amber-700',
                sms: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                email: 'border-blue-200 bg-blue-50 text-blue-700',
            }[channel] || 'border-slate-200 bg-slate-100 text-slate-600';
        },

        statusBadgeClass(status) {
            return {
                sent: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                failed: 'border-rose-200 bg-rose-50 text-rose-700',
                pending: 'border-amber-200 bg-amber-50 text-amber-700',
            }[status] || 'border-slate-200 bg-slate-100 text-slate-600';
        },

        statusLabel(status) {
            if (!status) return '-';
            return status.charAt(0).toUpperCase() + status.slice(1);
        },

        audienceText(audience) {
            return {
                all_vendors: 'All Vendors',
                all_drivers: 'All Riders',
                all: 'Vendors & Riders',
            }[audience] || '-';
        },

        formatDate(val) {
            if (!val) return '-';
            return new Date(val).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        },

        formatTime(val) {
            if (!val) return '';
            return new Date(val).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        },
    };
}
</script>
@endpush
@endsection
