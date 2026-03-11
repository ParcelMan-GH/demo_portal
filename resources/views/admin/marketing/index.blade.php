@extends('admin.layouts.app')
@section('title', 'Marketing Broadcasts')
@section('breadcrumb-parent', 'System')
@section('breadcrumb-current', 'Marketing')

@section('content')
<div class="space-y-6" x-data="marketingPage()">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Marketing Broadcasts</h1>
            <p class="mt-1 text-sm text-slate-500">Send push notifications, emails, and SMS to vendors and drivers.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Push
            </span>
            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> SMS
            </span>
            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Email
            </span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/70 p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center flex-shrink-0 shadow-inner">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($totalBroadcasts) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Total Broadcasts</p>
            </div>
        </div>

        <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/70 p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 flex items-center justify-center flex-shrink-0 shadow-inner">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($broadcastsByChannel['push'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Push Sent</p>
            </div>
        </div>

        <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/70 p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center flex-shrink-0 shadow-inner">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($broadcastsByChannel['sms'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">SMS Sent</p>
            </div>
        </div>

        <div class="bg-white/90 backdrop-blur-2xl rounded-2xl border border-slate-200/70 p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center flex-shrink-0 shadow-inner">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($broadcastsByChannel['email'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Emails Sent</p>
            </div>
        </div>
    </div>

    <!-- Compose Broadcast -->
    <div class="bg-white/90 backdrop-blur-2xl rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-200/50 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center shadow-lg shadow-slate-900/20">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Compose Broadcast</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Select a channel and compose your message</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <form @@submit.prevent="send()" class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- Left: Form fields (3 cols) -->
                <div class="lg:col-span-3 space-y-5">

                    <!-- Channel Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Channel</label>
                        <div class="flex gap-2">
                            <button type="button" @@click="form.channel = 'push'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border transition-all duration-200"
                                :class="form.channel === 'push'
                                    ? 'bg-slate-900 text-white border-slate-900 shadow-lg shadow-slate-900/20'
                                    : 'bg-white/70 text-slate-600 border-slate-200/70 hover:bg-slate-50'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                Push
                            </button>
                            <button type="button" @@click="form.channel = 'sms'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border transition-all duration-200"
                                :class="form.channel === 'sms'
                                    ? 'bg-slate-900 text-white border-slate-900 shadow-lg shadow-slate-900/20'
                                    : 'bg-white/70 text-slate-600 border-slate-200/70 hover:bg-slate-50'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                SMS
                            </button>
                            <button type="button" @@click="form.channel = 'email'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border transition-all duration-200"
                                :class="form.channel === 'email'
                                    ? 'bg-slate-900 text-white border-slate-900 shadow-lg shadow-slate-900/20'
                                    : 'bg-white/70 text-slate-600 border-slate-200/70 hover:bg-slate-50'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Email
                            </button>
                        </div>
                    </div>

                    <!-- Push: Title -->
                    <div x-show="form.channel === 'push'" x-transition>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Notification Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.title" maxlength="200" placeholder="e.g. New Feature Available!"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition">
                        <p class="mt-1 text-[11px] text-slate-400" x-text="`${form.title.length}/200 characters`"></p>
                    </div>

                    <!-- Email: Subject -->
                    <div x-show="form.channel === 'email'" x-transition>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Email Subject <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.subject" maxlength="200" placeholder="e.g. Important Update for You"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition">
                        <p class="mt-1 text-[11px] text-slate-400" x-text="`${form.subject.length}/200 characters`"></p>
                    </div>

                    <!-- Email: Template Picker -->
                    <div x-show="form.channel === 'email'" x-transition>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Template</label>
                        <div class="relative">
                            <select x-model="form.email_template"
                                class="w-full appearance-none px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition pr-10">
                                <option value="custom">Custom Message</option>
                                <template x-for="tpl in emailTemplates" :key="tpl.name">
                                    <option :value="tpl.name" x-text="tpl.name"></option>
                                </template>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400" x-show="form.email_template !== 'custom'">Template will be rendered with the subject and recipient name.</p>
                    </div>

                    <!-- Message Body -->
                    <div x-show="form.channel !== 'email' || form.email_template === 'custom'" x-transition>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            <span x-text="form.channel === 'sms' ? 'SMS Message' : (form.channel === 'email' ? 'Email Body' : 'Message Body')"></span>
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="form.body" :rows="form.channel === 'sms' ? 3 : 5" placeholder="Enter your message here..."
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition resize-none"></textarea>
                        <div class="mt-1 flex items-center justify-between">
                            <p class="text-[11px] text-slate-400" x-show="form.channel === 'sms'" x-text="`${form.body.length}/160 (${smsSegments()} segment${smsSegments() > 1 ? 's' : ''})`"></p>
                            <p class="text-[11px] text-slate-400" x-show="form.channel !== 'sms'" x-text="`${form.body.length} characters`"></p>
                        </div>
                    </div>

                    <!-- Body for email with template (optional extra content) -->
                    <div x-show="form.channel === 'email' && form.email_template !== 'custom'" x-transition>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Message Content <span class="text-red-500">*</span>
                        </label>
                        <textarea x-model="form.body" rows="4" placeholder="Content to pass to the template..."
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition resize-none"></textarea>
                        <p class="mt-1 text-[11px] text-slate-400">This content will be available as the body variable in the template.</p>
                    </div>

                    <!-- Audience Select -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Target Audience <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="form.audience"
                                class="w-full appearance-none px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition pr-10">
                                <option value="all_vendors" x-text="`All Vendors (${audienceCount('vendors')} reachable)`"></option>
                                <option value="all_drivers" x-text="`All Drivers (${audienceCount('drivers')} reachable)`"></option>
                                <option value="all" x-text="`Everyone (${audienceCount('vendors') + audienceCount('drivers')} reachable)`"></option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Only active recipients with a registered
                            <span x-text="form.channel === 'push' ? 'FCM token' : (form.channel === 'sms' ? 'phone number' : 'email address')"></span>
                            will be reached.
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-1">
                        <button type="submit" :disabled="form.submitting || !canSend()"
                            class="group relative flex items-center gap-2.5 px-6 py-2.5 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-slate-900/25 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                            <template x-if="form.submitting">
                                <svg class="w-4 h-4 animate-spin relative" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <template x-if="!form.submitting">
                                <svg class="w-4 h-4 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </template>
                            <span class="relative" x-text="form.submitting ? 'Sending...' : 'Send Broadcast'"></span>
                        </button>
                    </div>

                    <!-- Result message -->
                    <template x-if="result">
                        <div class="flex items-start gap-3 p-4 rounded-xl border text-sm"
                            :class="result.success ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
                            <template x-if="result.success">
                                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            <template x-if="!result.success">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            <div>
                                <p class="font-semibold" x-text="result.success ? 'Broadcast Sent!' : 'Error'"></p>
                                <p class="mt-0.5 text-xs opacity-80" x-text="result.message"></p>
                                <template x-if="result.success && result.data">
                                    <div class="mt-2 flex items-center gap-4 text-xs font-medium">
                                        <span class="flex items-center gap-1">
                                            <span class="w-2 h-2 bg-emerald-500 rounded-full inline-block"></span>
                                            <span x-text="`${result.data.sent} delivered`"></span>
                                        </span>
                                        <span class="flex items-center gap-1" x-show="result.data.failed > 0">
                                            <span class="w-2 h-2 bg-amber-400 rounded-full inline-block"></span>
                                            <span x-text="`${result.data.failed} failed`"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Right: Live Preview (2 cols) -->
                <div class="lg:col-span-2 flex flex-col">
                    <p class="text-xs font-semibold text-slate-700 mb-3">Live Preview</p>
                    <div class="flex-1 bg-slate-100/60 rounded-2xl p-5 flex items-center justify-center min-h-[320px]">
                        <div class="w-full max-w-xs">

                            <!-- Push Preview -->
                            <div x-show="form.channel === 'push'" x-transition>
                                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-200">
                                    <div class="bg-slate-800 px-4 py-1.5 flex items-center justify-between">
                                        <span class="text-white text-[10px] font-medium">9:41 AM</span>
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-2.5 h-2.5 rounded-full bg-white/30"></div>
                                            <div class="w-2.5 h-2.5 rounded-full bg-white/30"></div>
                                            <div class="w-2.5 h-2.5 rounded-full bg-white/30"></div>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 shadow-sm">
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="text-[11px] font-bold text-slate-900 truncate" x-text="form.title || 'Notification Title'"></p>
                                                        <span class="text-[10px] text-slate-400 whitespace-nowrap">now</span>
                                                    </div>
                                                    <p class="text-[11px] text-slate-600 mt-0.5 line-clamp-3" x-text="form.body || 'Your notification message will appear here...'"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="mt-3 text-center text-[10px] text-slate-400">
                                            Sending to: <span class="font-semibold text-slate-600" x-text="audienceLabel()"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- SMS Preview -->
                            <div x-show="form.channel === 'sms'" x-transition>
                                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-200">
                                    <div class="bg-emerald-600 px-4 py-2 flex items-center gap-2.5">
                                        <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                        <span class="text-white text-xs font-semibold">Messages</span>
                                    </div>
                                    <div class="p-4 bg-slate-50 min-h-[160px] flex flex-col justify-end">
                                        <div class="flex justify-start mb-2">
                                            <div class="max-w-[85%] bg-white rounded-2xl rounded-bl-md px-3.5 py-2.5 shadow-sm border border-slate-100">
                                                <p class="text-[11px] text-slate-800 whitespace-pre-wrap" x-text="form.body || 'Your SMS message will appear here...'"></p>
                                                <p class="text-[9px] text-slate-400 mt-1 text-right">now</p>
                                            </div>
                                        </div>
                                        <p class="text-center text-[10px] text-slate-400 mt-2">
                                            Sending to: <span class="font-semibold text-slate-600" x-text="audienceLabel()"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Email Preview -->
                            <div x-show="form.channel === 'email'" x-transition>
                                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-200">
                                    <div class="bg-blue-600 px-4 py-2 flex items-center gap-2.5">
                                        <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-white text-xs font-semibold">Email</span>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] text-slate-400 font-medium w-10">From:</span>
                                                <span class="text-[11px] text-slate-700 font-medium">{{ config('mail.from.name', 'Parcelman') }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] text-slate-400 font-medium w-10">Subj:</span>
                                                <span class="text-[11px] text-slate-800 font-semibold truncate" x-text="form.subject || 'Email Subject'"></span>
                                            </div>
                                        </div>
                                        <div class="border-t border-slate-100 pt-3">
                                            <template x-if="form.email_template !== 'custom'">
                                                <div class="text-center py-4">
                                                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mx-auto mb-2">
                                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
                                                        </svg>
                                                    </div>
                                                    <p class="text-[11px] text-slate-500">Using template:</p>
                                                    <p class="text-[11px] font-semibold text-slate-800" x-text="form.email_template"></p>
                                                </div>
                                            </template>
                                            <template x-if="form.email_template === 'custom'">
                                                <p class="text-[11px] text-slate-600 line-clamp-6 whitespace-pre-wrap" x-text="form.body || 'Your email body will appear here...'"></p>
                                            </template>
                                        </div>
                                        <p class="text-center text-[10px] text-slate-400 pt-1 border-t border-slate-100">
                                            Sending to: <span class="font-semibold text-slate-600" x-text="audienceLabel()"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Recent Broadcasts Table -->
    <div class="bg-white/90 backdrop-blur-2xl rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-200/50 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center shadow-inner">
                        <svg class="w-4.5 h-4.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-sm font-bold text-slate-800">Recent Broadcasts</h2>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700" x-text="tableMeta.total + ' Total'"></span>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-3 border-b border-slate-100 bg-slate-50/30">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <!-- Search -->
                <div class="relative flex-1 max-w-xs">
                    <input type="text" x-model="filters.search" @@input.debounce.500ms="applyFilters()" placeholder="Search broadcasts..."
                        class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors">
                    <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Channel Filter -->
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @@click="open = !open"
                        class="inline-flex items-center justify-between gap-2 px-3 py-2 min-w-[140px] border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                        <span x-text="filters.channelLabel || 'All channels'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @@click.away="open = false" x-transition
                        class="absolute left-0 mt-2 w-40 rounded-2xl border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                        <button type="button" @@click="filters.channel = ''; filters.channelLabel = ''; applyFilters(); open = false"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100/70"
                            :class="!filters.channel ? 'bg-slate-100/70' : ''">All channels</button>
                        <button type="button" @@click="filters.channel = 'push'; filters.channelLabel = 'Push'; applyFilters(); open = false"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100/70"
                            :class="filters.channel === 'push' ? 'bg-slate-100/70' : ''">Push</button>
                        <button type="button" @@click="filters.channel = 'sms'; filters.channelLabel = 'SMS'; applyFilters(); open = false"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100/70"
                            :class="filters.channel === 'sms' ? 'bg-slate-100/70' : ''">SMS</button>
                        <button type="button" @@click="filters.channel = 'email'; filters.channelLabel = 'Email'; applyFilters(); open = false"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100/70"
                            :class="filters.channel === 'email' ? 'bg-slate-100/70' : ''">Email</button>
                    </div>
                </div>

                <!-- Status Filter -->
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @@click="open = !open"
                        class="inline-flex items-center justify-between gap-2 px-3 py-2 min-w-[130px] border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                        <span x-text="filters.statusLabel || 'All statuses'"></span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @@click.away="open = false" x-transition
                        class="absolute left-0 mt-2 w-36 rounded-2xl border border-slate-200/70 bg-white/95 backdrop-blur-xl shadow-2xl p-2 z-50" style="display: none;">
                        <button type="button" @@click="filters.status = ''; filters.statusLabel = ''; applyFilters(); open = false"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100/70"
                            :class="!filters.status ? 'bg-slate-100/70' : ''">All statuses</button>
                        <button type="button" @@click="filters.status = 'sent'; filters.statusLabel = 'Sent'; applyFilters(); open = false"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100/70"
                            :class="filters.status === 'sent' ? 'bg-slate-100/70' : ''">Sent</button>
                        <button type="button" @@click="filters.status = 'failed'; filters.statusLabel = 'Failed'; applyFilters(); open = false"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100/70"
                            :class="filters.status === 'failed' ? 'bg-slate-100/70' : ''">Failed</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto relative">
            <div x-show="tableLoading" x-cloak x-transition.opacity.duration.150ms class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10"></div>

            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-200/50">
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Channel</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Title</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Message</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                        <th class="px-4 py-3 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/70">
                    <template x-if="!tableLoading && broadcasts.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500">No broadcasts found</p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="log in broadcasts" :key="log.id">
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-xs text-slate-700 font-medium" x-text="formatDate(log.created_at)"></span>
                                <span class="block text-[10px] text-slate-400" x-text="formatTime(log.created_at)"></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-amber-100 text-amber-700':   log.channel === 'push',
                                        'bg-emerald-100 text-emerald-700': log.channel === 'sms',
                                        'bg-blue-100 text-blue-700':     log.channel === 'email'
                                    }"
                                    x-text="log.channel ? log.channel.toUpperCase() : '—'"></span>
                            </td>
                            <td class="px-4 py-3 max-w-[180px]">
                                <p class="text-xs font-medium text-slate-800 truncate" x-text="log.title || '—'"></p>
                            </td>
                            <td class="px-4 py-3 max-w-[260px]">
                                <p class="text-xs text-slate-500 truncate" x-text="log.body || '—'"></p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                    :class="log.notifiable_type === 'Vendor' ? 'bg-blue-50 text-blue-700' : 'bg-violet-50 text-violet-700'"
                                    x-text="log.notifiable_type || '—'"></span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': log.status === 'sent',
                                        'bg-rose-100 text-rose-700':      log.status === 'failed',
                                        'bg-slate-100 text-slate-600':    log.status !== 'sent' && log.status !== 'failed'
                                    }">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :class="{
                                            'bg-emerald-500': log.status === 'sent',
                                            'bg-rose-500':    log.status === 'failed',
                                            'bg-slate-400':   log.status !== 'sent' && log.status !== 'failed'
                                        }"></span>
                                    <span x-text="log.status ? log.status.charAt(0).toUpperCase() + log.status.slice(1) : '—'"></span>
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-2.5 border-t border-slate-200/50 bg-slate-50/30">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-xs text-slate-600">
                    Showing <span x-text="tableMeta.from || 0"></span> to <span x-text="tableMeta.to || 0"></span> of <span x-text="tableMeta.total || 0"></span> results
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-xs font-medium text-slate-600">
                        Page <span x-text="tableMeta.current_page || 1"></span> of <span x-text="tableMeta.last_page || 1"></span>
                    </div>
                    <div class="flex space-x-1">
                        <button @@click="goToPage(1)" :disabled="tableMeta.current_page === 1"
                            :class="tableMeta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M20 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button @@click="goToPage(tableMeta.current_page - 1)" :disabled="tableMeta.current_page === 1"
                            :class="tableMeta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button @@click="goToPage(tableMeta.current_page + 1)" :disabled="tableMeta.current_page >= tableMeta.last_page"
                            :class="tableMeta.current_page >= tableMeta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <button @@click="goToPage(tableMeta.last_page)" :disabled="tableMeta.current_page >= tableMeta.last_page"
                            :class="tableMeta.current_page >= tableMeta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white/80'"
                            class="w-7 h-7 border border-slate-200/70 rounded-lg bg-white/50 text-slate-600 flex items-center justify-center transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M4 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function marketingPage() {
    return {
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

        // Audience counts per channel
        counts: {
            push:  { vendors: {{ $vendorCountPush }},  drivers: {{ $driverCountPush }} },
            sms:   { vendors: {{ $vendorCountSms }},   drivers: {{ $driverCountSms }} },
            email: { vendors: {{ $vendorCountEmail }},  drivers: {{ $driverCountEmail }} },
        },

        emailTemplates: @json($emailTemplates),

        // Broadcasts table
        broadcasts: [],
        tableLoading: false,
        tableMeta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        filters: { search: '', channel: '', channelLabel: '', status: '', statusLabel: '' },

        init() {
            this.loadBroadcasts();
        },

        audienceCount(type) {
            return this.counts[this.form.channel]?.[type] || 0;
        },

        audienceLabel() {
            const labels = { all_vendors: 'All Vendors', all_drivers: 'All Drivers', all: 'Vendors & Drivers' };
            return labels[this.form.audience] || this.form.audience;
        },

        smsSegments() {
            return Math.ceil(this.form.body.length / 160) || 1;
        },

        canSend() {
            if (!this.form.body.trim() && (this.form.channel !== 'email' || this.form.email_template === 'custom')) return false;
            if (this.form.channel === 'push' && !this.form.title.trim()) return false;
            if (this.form.channel === 'email' && !this.form.subject.trim()) return false;
            if (this.form.channel === 'email' && this.form.email_template !== 'custom' && !this.form.body.trim()) return false;
            return true;
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
                        channel:        this.form.channel,
                        title:          this.form.title,
                        body:           this.form.body,
                        subject:        this.form.subject,
                        audience:       this.form.audience,
                        email_template: this.form.email_template,
                    }),
                });

                const data = await resp.json();

                if (!resp.ok) {
                    this.result = { success: false, message: data.message || 'Validation failed.' };
                    return;
                }

                this.result = data;

                if (data.success) {
                    this.form.title   = '';
                    this.form.body    = '';
                    this.form.subject = '';
                    this.loadBroadcasts();
                }
            } catch (e) {
                this.result = { success: false, message: 'Request failed. Please try again.' };
            } finally {
                this.form.submitting = false;
            }
        },

        // ── Table methods ──────────────────────────────

        async loadBroadcasts() {
            this.tableLoading = true;

            const params = new URLSearchParams({
                page: this.tableMeta.current_page,
                per_page: this.tableMeta.per_page,
            });

            if (this.filters.search)  params.set('search',  this.filters.search);
            if (this.filters.channel) params.set('channel', this.filters.channel);
            if (this.filters.status)  params.set('status',  this.filters.status);

            try {
                const resp = await fetch('{{ route("admin.marketing.data") }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!resp.ok) throw new Error('Failed to fetch');

                const json = await resp.json();
                this.broadcasts = json.data;
                this.tableMeta  = json.meta;
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

        goToPage(page) {
            if (page < 1 || page > this.tableMeta.last_page) return;
            this.tableMeta.current_page = page;
            this.loadBroadcasts();
        },

        formatDate(val) {
            if (!val) return '—';
            const d = new Date(val);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        },

        formatTime(val) {
            if (!val) return '';
            const d = new Date(val);
            return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        },
    };
}
</script>
@endpush
@endsection
