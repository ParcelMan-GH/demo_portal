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
            <p class="mt-1 text-sm text-slate-500">Send push notifications to vendors and drivers instantly.</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-400 bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span>Firebase Push (FCM)</span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Active Vendors with FCM -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($vendorCount) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Active Vendors with FCM</p>
            </div>
        </div>

        <!-- Active Drivers with FCM -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($driverCount) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Active Drivers with FCM</p>
            </div>
        </div>

        <!-- Total Broadcasts Sent -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($totalBroadcasts) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Total Broadcasts Logged</p>
            </div>
        </div>
    </div>

    <!-- Compose Broadcast -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <h2 class="text-sm font-bold text-slate-800">Send Broadcast Notification</h2>
        </div>

        <div class="p-6">
            <form @submit.prevent="send()" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Left: Form fields -->
                <div class="space-y-4">
                    <!-- Notification Title -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Notification Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            x-model="form.title"
                            maxlength="200"
                            placeholder="e.g. New Feature Available!"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition"
                            required
                        >
                        <p class="mt-1 text-[11px] text-slate-400" x-text="`${form.title.length}/200 characters`"></p>
                    </div>

                    <!-- Notification Body -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Message Body <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            x-model="form.body"
                            maxlength="1000"
                            rows="4"
                            placeholder="Enter the full notification message here..."
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition resize-none"
                            required
                        ></textarea>
                        <p class="mt-1 text-[11px] text-slate-400" x-text="`${form.body.length}/1000 characters`"></p>
                    </div>

                    <!-- Audience Select -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Target Audience <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select
                                x-model="form.audience"
                                class="w-full appearance-none px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 transition pr-10"
                            >
                                <option value="all_vendors">All Vendors ({{ $vendorCount }} reachable)</option>
                                <option value="all_drivers">All Drivers ({{ $driverCount }} reachable)</option>
                                <option value="all">Everyone — Vendors &amp; Drivers ({{ $vendorCount + $driverCount }} reachable)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-400">Only active recipients with a registered FCM token will receive this notification.</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.submitting || !form.title.trim() || !form.body.trim()"
                            class="flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-sm"
                        >
                            <template x-if="form.submitting">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <template x-if="!form.submitting">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </template>
                            <span x-text="form.submitting ? 'Sending...' : 'Send Broadcast'"></span>
                        </button>
                    </div>

                    <!-- Result message -->
                    <template x-if="result">
                        <div
                            class="flex items-start gap-3 p-4 rounded-xl border text-sm"
                            :class="result.success
                                ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                                : 'bg-red-50 border-red-200 text-red-800'"
                        >
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

                <!-- Right: Live Preview -->
                <div class="flex flex-col">
                    <p class="text-xs font-semibold text-slate-700 mb-3">Notification Preview</p>
                    <div class="flex-1 bg-slate-100 rounded-2xl p-4 flex items-center justify-center">
                        <div class="w-full max-w-xs">
                            <!-- Phone mockup -->
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-200">
                                <!-- Phone status bar -->
                                <div class="bg-slate-800 px-4 py-1.5 flex items-center justify-between">
                                    <span class="text-white text-[10px] font-medium">9:41 AM</span>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0119 12.55M5 12.55a10.94 10.94 0 015.17-2.39M10.71 5.05A16 16 0 0122.56 9M1.42 9a15.91 15.91 0 014.7-2.88M8.53 16.11a6 6 0 016.95 0M12 20h.01"/></svg>
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/></svg>
                                    </div>
                                </div>
                                <!-- Notification bubble -->
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
                                                    <p class="text-[11px] font-bold text-slate-900 truncate"
                                                       x-text="form.title || 'Notification Title'"></p>
                                                    <span class="text-[10px] text-slate-400 whitespace-nowrap">now</span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 mt-0.5 line-clamp-3"
                                                   x-text="form.body || 'Your notification message will appear here...'"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-center text-[10px] text-slate-400">
                                        Sending to:
                                        <span class="font-semibold text-slate-600" x-text="audienceLabel()"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Recent Broadcasts Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800">Recent Broadcasts</h2>
            <span class="text-xs text-slate-400">Last 10 entries</span>
        </div>

        @if($recentCampaigns->isEmpty())
        <div class="px-6 py-12 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-500">No broadcasts sent yet</p>
            <p class="text-xs text-slate-400 mt-1">Use the form above to send your first broadcast notification.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50/70">
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Recipient</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Channel</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentCampaigns as $campaign)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <!-- Sent At -->
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <span class="text-xs text-slate-600 font-medium">{{ $campaign->created_at->format('M j, Y') }}</span>
                            <span class="block text-[11px] text-slate-400">{{ $campaign->created_at->format('H:i') }}</span>
                        </td>

                        <!-- Title -->
                        <td class="px-6 py-3.5 max-w-[180px]">
                            <p class="text-xs font-semibold text-slate-800 truncate" title="{{ $campaign->title }}">
                                {{ $campaign->title }}
                            </p>
                        </td>

                        <!-- Message Body -->
                        <td class="px-6 py-3.5 max-w-[260px]">
                            <p class="text-xs text-slate-500 truncate" title="{{ $campaign->body }}">
                                {{ $campaign->body }}
                            </p>
                        </td>

                        <!-- Recipient Type -->
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            @php
                                $recipientType = class_basename($campaign->notifiable_type);
                            @endphp
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-lg
                                {{ $recipientType === 'Vendor' ? 'bg-blue-50 text-blue-700' : ($recipientType === 'Driver' ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-600') }}">
                                @if($recipientType === 'Vendor')
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                @elseif($recipientType === 'Driver')
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                @endif
                                {{ $recipientType }}
                            </span>
                        </td>

                        <!-- Channel -->
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                {{ strtoupper($campaign->channel) }}
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="px-6 py-3.5 whitespace-nowrap">
                            @if($campaign->status === 'sent')
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                    Sent
                                </span>
                            @elseif($campaign->status === 'failed')
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-red-50 text-red-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
                                    Failed
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 inline-block"></span>
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function marketingPage() {
    return {
        form: {
            title: '',
            body: '',
            audience: 'all_vendors',
            submitting: false,
        },
        result: null,

        audienceLabel() {
            const labels = {
                all_vendors: 'All Vendors',
                all_drivers: 'All Drivers',
                all:         'Vendors & Drivers',
            };
            return labels[this.form.audience] || this.form.audience;
        },

        async send() {
            this.form.submitting = true;
            this.result = null;

            try {
                const resp = await fetch('{{ route('admin.marketing.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        title:    this.form.title,
                        body:     this.form.body,
                        audience: this.form.audience,
                    }),
                });

                const data = await resp.json();
                this.result = data;

                if (data.success) {
                    this.form.title = '';
                    this.form.body  = '';
                }
            } catch (e) {
                this.result = { success: false, message: 'Request failed. Please try again.' };
            } finally {
                this.form.submitting = false;
            }
        },
    };
}
</script>
@endpush
@endsection
