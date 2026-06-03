@extends('admin.layouts.app')

@section('title', 'Settings')
@section('breadcrumb-parent', 'System')
@section('breadcrumb-current', 'Settings')

@section('content')
@php
    $tabGroups = [
        'General' => ['platform', 'delivery', 'pickup-vehicles', 'bus-stations', 'delivery-failure-reasons', 'delivery-delay-reasons', 'pricing'],
        'Communication' => ['sms', 'mail', 'push', 'email-templates'],
        'Logs' => ['email-logs', 'sms-logs', 'otp-logs', 'admin-audit-logs', 'notification-logs'],
        'System' => ['storage', 'health', 'logs'],
    ];
    $readOnlyTabs = ['health', 'logs', 'email-logs', 'sms-logs', 'otp-logs', 'admin-audit-logs', 'email-templates', 'notification-logs', 'pickup-vehicles', 'bus-stations', 'delivery-failure-reasons', 'delivery-delay-reasons'];
    $activeGroup = collect($tabGroups)->filter(fn ($keys) => in_array($activeTab, $keys, true))->keys()->first() ?? 'General';
    $canEditSettings = auth('admin')->user()?->hasPermission('settings.edit') ?? false;
@endphp

<div class="space-y-5" x-data="settingsManager()">
    <div x-show="message" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         class="fixed right-5 top-5 z-[100] max-w-md rounded-2xl border bg-white px-4 py-3 shadow-2xl shadow-slate-900/15"
         :class="messageType === 'success' ? 'border-emerald-200' : 'border-rose-200'">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="messageType === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                <template x-if="messageType === 'success'">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="messageType === 'error'">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </template>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-900" x-text="messageType === 'success' ? 'Done' : 'Needs attention'"></p>
                <p class="mt-0.5 text-sm font-semibold text-slate-600" x-text="message"></p>
            </div>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="xl:sticky xl:top-20 xl:self-start">
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900">Settings</h2>
                            <p class="truncate text-sm text-slate-500">System controls</p>
                        </div>
                    </div>
                </div>

                <nav class="max-h-[calc(100vh-10rem)] overflow-y-auto p-3">
                    @foreach($tabGroups as $group => $keys)
                        <div class="{{ !$loop->first ? 'mt-4' : '' }}">
                            <p class="mb-2 px-3 text-[10px] font-black uppercase tracking-wide text-slate-400">{{ $group }}</p>
                            <div class="space-y-1">
                                @foreach($keys as $key)
                                    @continue(!isset($tabs[$key]))
                                    <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                                       class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $activeTab === $key ? 'bg-orange-50 text-orange-700 ring-1 ring-orange-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $activeTab === $key ? 'bg-white text-orange-700 ring-1 ring-orange-100' : 'bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-slate-700' }}">
                                            @include('admin.settings.partials.tab-icon', ['icon' => $tabs[$key]['icon'], 'active' => false])
                                        </span>
                                        <span class="min-w-0 flex-1 truncate">{{ $tabs[$key]['label'] }}</span>
                                        @if($activeTab === $key)
                                            <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="mb-2 px-3 text-[10px] font-black uppercase tracking-wide text-slate-400">Data Management</p>
                        <div class="space-y-1">
                            <a href="{{ route('admin.locations.index') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-slate-700">
                                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                </span>
                                <span class="min-w-0 flex-1 truncate">Locations</span>
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </aside>

        <div class="min-w-0 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-lg shadow-slate-300/30">
        <div class="border-b border-slate-200/60 px-5 py-4">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                        @include('admin.settings.partials.tab-icon', ['icon' => $tabs[$activeTab]['icon'], 'active' => false])
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-wide text-orange-600">{{ $activeGroup }}</p>
                        <h1 class="text-lg font-extrabold text-slate-900">{{ $tabs[$activeTab]['label'] }}</h1>
                        <p class="text-sm text-slate-500">
                            @if(in_array($activeTab, ['email-logs', 'sms-logs', 'otp-logs', 'admin-audit-logs', 'notification-logs'], true))
                                Review platform activity and delivery traces.
                            @elseif($activeTab === 'health')
                                Check service health and operational readiness.
                            @elseif($activeTab === 'logs')
                                Inspect application logs and clear old entries.
                            @elseif($activeTab === 'email-templates')
                                Review available system email templates.
                            @elseif($activeTab === 'storage')
                                Choose local storage or an S3-compatible bucket.
                            @elseif($activeTab === 'pricing')
                                Manage vendor commission and payout rules.
                            @elseif($activeTab === 'pickup-vehicles')
                                Manage optional vehicle requests vendors can add to pickup requests.
                            @elseif($activeTab === 'bus-stations')
                                Manage bus stations riders can choose during courier handoff.
                            @elseif($activeTab === 'delivery-failure-reasons')
                                Manage reusable reasons for failed deliveries, not-received reports, and delivery issues.
                            @elseif($activeTab === 'delivery-delay-reasons')
                                Manage reasons used when delivery ETAs slip or delay notices are sent.
                            @else
                                Configure values used across the platform.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    @if($activeTab === 'email-templates')
                        <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold text-slate-700">{{ $tabData['templates']->count() }} templates</span>
                        <span class="inline-flex items-center rounded-xl bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700 ring-1 ring-emerald-100">{{ $tabData['templates']->where('is_enabled', true)->count() }} enabled</span>
                        <button type="button"
                                @@click="$dispatch('email-template-create')"
                                class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Create Template
                        </button>
                    @elseif($activeTab === 'pickup-vehicles')
                        @if($canEditSettings)
                            <button type="button"
                                    @@click="$dispatch('pickup-vehicle-create')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Vehicle
                            </button>
                        @endif
                    @elseif($activeTab === 'bus-stations')
                        @if($canEditSettings)
                            <button type="button"
                                    @@click="$dispatch('bus-station-create')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Station
                            </button>
                        @endif
                    @elseif($activeTab === 'delivery-failure-reasons')
                        @if($canEditSettings)
                            <button type="button"
                                    @@click="$dispatch('delivery-failure-reason-create')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Reason
                            </button>
                        @endif
                    @elseif($activeTab === 'delivery-delay-reasons')
                        @if($canEditSettings)
                            <button type="button"
                                    @@click="$dispatch('delivery-delay-reason-create')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Reason
                            </button>
                        @endif
                    @elseif($activeTab === 'notification-logs')
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/></svg>
                                View
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <template x-for="col in $store.notifLogs?.columns || []" :key="col.key">
                                    <button type="button" @@click="$dispatch('notif-toggle-column', col.key)" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <span x-text="col.label"></span>
                                        <svg x-show="$store.notifLogs?.visibleColumns?.[col.key]" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition class="absolute right-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl" style="display: none;">
                                <button type="button" @@click="$dispatch('notif-export-csv'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">CSV</button>
                                <button type="button" @@click="$dispatch('notif-print'); open = false" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-50">Print</button>
                            </div>
                        </div>
                        <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold text-slate-700" x-text="($store.notifLogs?.total || 0) + ' logs'"></span>
                    @elseif(!in_array($activeTab, $readOnlyTabs, true))
                        <button type="button"
                                @@click="saveSettings()"
                                :disabled="saving"
                                class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <template x-if="!saving">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="saving">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            </template>
                            <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-5">
            @include('admin.settings.tabs.' . $activeTab, ['settings' => $settings, 'tabData' => $tabData])
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
window.settingsConfig = {
    saveEndpoint: @json(route('admin.settings.save')),
    uploadEndpoint: @json(route('admin.settings.upload')),
    logsDataEndpoint: @json(route('admin.settings.logs.data')),
    logsExportEndpoint: @json(route('admin.settings.logs.export')),
    logsClearEndpoint: @json(route('admin.settings.logs.clear')),
    emailLogsDataEndpoint: @json(route('admin.settings.email-logs.data')),
    smsLogsDataEndpoint: @json(route('admin.settings.sms-logs.data')),
    otpLogsDataEndpoint: @json(route('admin.settings.otp-logs.data')),
    adminAuditLogsDataEndpoint: @json(route('admin.settings.admin-audit-logs.data')),
    adminAuditLogsExportEndpoint: @json(route('admin.settings.admin-audit-logs.export')),
    notificationLogsDataEndpoint: @json(route('admin.notifications.data')),
    pickupVehiclesStoreEndpoint: @json(route('admin.settings.pickup-vehicles.store')),
    pickupVehiclesUpdateEndpoint: @json(route('admin.settings.pickup-vehicles.update', ['pickupVehicleType' => '__ID__'])),
    pickupVehiclesToggleEndpoint: @json(route('admin.settings.pickup-vehicles.toggle', ['pickupVehicleType' => '__ID__'])),
    pickupVehiclesDeleteEndpoint: @json(route('admin.settings.pickup-vehicles.delete', ['pickupVehicleType' => '__ID__'])),
    busStationsStoreEndpoint: @json(route('admin.settings.bus-stations.store')),
    busStationsUpdateEndpoint: @json(route('admin.settings.bus-stations.update', ['busStation' => '__ID__'])),
    busStationsToggleEndpoint: @json(route('admin.settings.bus-stations.toggle', ['busStation' => '__ID__'])),
    busStationsDeleteEndpoint: @json(route('admin.settings.bus-stations.delete', ['busStation' => '__ID__'])),
    deliveryFailureReasonsStoreEndpoint: @json(route('admin.settings.delivery-failure-reasons.store')),
    deliveryFailureReasonsUpdateEndpoint: @json(route('admin.settings.delivery-failure-reasons.update', ['deliveryFailureReason' => '__ID__'])),
    deliveryFailureReasonsToggleEndpoint: @json(route('admin.settings.delivery-failure-reasons.toggle', ['deliveryFailureReason' => '__ID__'])),
    deliveryFailureReasonsDeleteEndpoint: @json(route('admin.settings.delivery-failure-reasons.delete', ['deliveryFailureReason' => '__ID__'])),
    deliveryDelayReasonsStoreEndpoint: @json(route('admin.settings.delivery-delay-reasons.store')),
    deliveryDelayReasonsUpdateEndpoint: @json(route('admin.settings.delivery-delay-reasons.update', ['deliveryDelayReason' => '__ID__'])),
    deliveryDelayReasonsToggleEndpoint: @json(route('admin.settings.delivery-delay-reasons.toggle', ['deliveryDelayReason' => '__ID__'])),
    deliveryDelayReasonsDeleteEndpoint: @json(route('admin.settings.delivery-delay-reasons.delete', ['deliveryDelayReason' => '__ID__'])),
    testEmailEndpoint: @json(route('admin.settings.test-email')),
    testSmsEndpoint: @json(route('admin.settings.test-sms')),
    clearCacheEndpoint: @json(route('admin.settings.clear-cache')),
    activeTab: @json($activeTab),
    csrfToken: @json(csrf_token())
};

window.setupSettingsDateRangePicker = function(component, input, fromField, toField, onChange) {
    const setField = (path, value) => {
        const parts = String(path).split('.');
        let target = component;
        while (parts.length > 1) {
            target = target[parts.shift()];
        }
        target[parts[0]] = value;
    };
    const getField = (path) => String(path).split('.').reduce((target, key) => target?.[key], component);

    const setupPicker = () => {
        if (!input || !window.$ || !window.moment || !window.$.fn.daterangepicker) return;

        const $input = window.$(input);
        if ($input.data('daterangepicker')) {
            $input.data('daterangepicker').remove();
            $input.off('.daterangepicker');
        }

        $input.daterangepicker({
            autoUpdateInput: false,
            alwaysShowCalendars: true,
            opens: 'right',
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear',
            },
            ranges: {
                'Today': [window.moment(), window.moment()],
                'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
            },
        });

        $input.on('apply.daterangepicker', (ev, picker) => {
            setField(fromField, picker.startDate.format('YYYY-MM-DD'));
            setField(toField, picker.endDate.format('YYYY-MM-DD'));
            $input.val(`${getField(fromField)} - ${getField(toField)}`);
            if (typeof onChange === 'function') onChange();
        });

        $input.on('cancel.daterangepicker', () => {
            setField(fromField, '');
            setField(toField, '');
            $input.val('');
            if (typeof onChange === 'function') onChange();
        });

        component.dateRangePicker = $input.data('daterangepicker');
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
};

document.addEventListener('alpine:init', () => {
    Alpine.data('settingsManager', () => ({
        saving: false,
        message: '',
        messageType: 'success',
        settings: @json($settings),

        async saveSettings() {
            this.saving = true;
            this.message = '';

            try {
                const response = await fetch(window.settingsConfig.saveEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.settingsConfig.csrfToken,
                    },
                    body: JSON.stringify({
                        tab: window.settingsConfig.activeTab,
                        settings: this.settings,
                    }),
                });

                const result = await response.json();

                if (response.ok) {
                    this.message = result.message || 'Settings saved successfully.';
                    this.messageType = 'success';
                } else {
                    throw new Error(result.message || 'Failed to save settings');
                }
            } catch (error) {
                this.message = error.message;
                this.messageType = 'error';
            } finally {
                this.saving = false;
                setTimeout(() => { this.message = ''; }, 5000);
            }
        },

        async uploadFile(key, event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('key', key);

            try {
                const response = await fetch(window.settingsConfig.uploadEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.settingsConfig.csrfToken,
                    },
                    body: formData,
                });

                const result = await response.json();

                if (response.ok) {
                    if (this.settings[key] && typeof this.settings[key] === 'object') {
                        this.settings[key].value = result.path;
                    } else {
                        this.settings[key] = result.path;
                    }
                    this.message = result.message || 'File uploaded successfully.';
                    this.messageType = 'success';
                } else {
                    throw new Error(result.message || 'Failed to upload file');
                }
            } catch (error) {
                this.message = error.message;
                this.messageType = 'error';
            }

            setTimeout(() => { this.message = ''; }, 5000);
        },

        async testEmail(email) {
            try {
                const response = await fetch(window.settingsConfig.testEmailEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.settingsConfig.csrfToken,
                    },
                    body: JSON.stringify({ email }),
                });

                const result = await response.json();
                this.message = result.message;
                this.messageType = result.success ? 'success' : 'error';
            } catch (error) {
                this.message = error.message;
                this.messageType = 'error';
            }

            setTimeout(() => { this.message = ''; }, 5000);
        },

        async testSms(phone) {
            try {
                const response = await fetch(window.settingsConfig.testSmsEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.settingsConfig.csrfToken,
                    },
                    body: JSON.stringify({ phone }),
                });

                const result = await response.json();
                this.message = result.message;
                this.messageType = result.success ? 'success' : 'error';
            } catch (error) {
                this.message = error.message;
                this.messageType = 'error';
            }

            setTimeout(() => { this.message = ''; }, 5000);
        },

        async clearCache() {
            if (!confirm('Are you sure you want to clear the cache?')) return;

            try {
                const response = await fetch(window.settingsConfig.clearCacheEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.settingsConfig.csrfToken,
                    },
                });

                const result = await response.json();
                this.message = result.message;
                this.messageType = result.success ? 'success' : 'error';
            } catch (error) {
                this.message = error.message;
                this.messageType = 'error';
            }

            setTimeout(() => { this.message = ''; }, 5000);
        },
    }));
});
</script>
@endpush
@endsection
