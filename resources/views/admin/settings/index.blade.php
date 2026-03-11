@extends('admin.layouts.app')

@section('title', 'Settings')
@section('breadcrumb-parent', 'System')
@section('breadcrumb-current', 'Settings')

@section('content')
<div class="flex gap-6" x-data="settingsManager()">
    <!-- Sidebar Navigation -->
    <div class="w-72 flex-shrink-0">
        <div class="bg-white/90 backdrop-blur-2xl rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-200/50 sticky top-20 overflow-hidden">
            <!-- Header with gradient -->
            <div class="relative px-5 py-4 border-b border-slate-100">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-slate-50/80"></div>
                <div class="relative flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center shadow-lg shadow-slate-900/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Settings</h3>
                        <p class="text-xs text-slate-500 font-medium">Configure your platform</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Groups -->
            <nav class="p-3 max-h-[calc(100vh-200px)] overflow-y-auto scrollbar-thin">
                <!-- General Section -->
                <div class="mb-4">
                    <div class="px-3 mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">General</span>
                    </div>
                    <div class="space-y-1">
                        @foreach(['platform' => $tabs['platform'], 'invoice' => $tabs['invoice']] as $key => $tab)
                        <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                           class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-300 {{ $activeTab === $key ? 'bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white shadow-lg shadow-slate-900/25' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300 {{ $activeTab === $key ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-slate-200' }}">
                                @include('admin.settings.partials.tab-icon', ['icon' => $tab['icon'], 'active' => $activeTab === $key])
                            </div>
                            <span class="truncate">{{ $tab['label'] }}</span>
                            @if($activeTab === $key)
                            <div class="absolute right-3 w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>

                <!-- Communications Section -->
                <div class="mb-4">
                    <div class="px-3 mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Communications</span>
                    </div>
                    <div class="space-y-1">
                        @foreach(['sms' => $tabs['sms'], 'mail' => $tabs['mail'], 'push' => $tabs['push'], 'email-templates' => $tabs['email-templates']] as $key => $tab)
                        <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                           class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-300 {{ $activeTab === $key ? 'bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white shadow-lg shadow-slate-900/25' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300 {{ $activeTab === $key ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-slate-200' }}">
                                @include('admin.settings.partials.tab-icon', ['icon' => $tab['icon'], 'active' => $activeTab === $key])
                            </div>
                            <span class="truncate">{{ $tab['label'] }}</span>
                            @if($activeTab === $key)
                            <div class="absolute right-3 w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>

                <!-- Logs Section -->
                <div class="mb-4">
                    <div class="px-3 mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Activity Logs</span>
                    </div>
                    <div class="space-y-1">
                        @foreach(['email-logs' => $tabs['email-logs'], 'sms-logs' => $tabs['sms-logs'], 'otp-logs' => $tabs['otp-logs'], 'admin-audit-logs' => $tabs['admin-audit-logs'], 'notification-logs' => $tabs['notification-logs']] as $key => $tab)
                        <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                           class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-300 {{ $activeTab === $key ? 'bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white shadow-lg shadow-slate-900/25' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300 {{ $activeTab === $key ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-slate-200' }}">
                                @include('admin.settings.partials.tab-icon', ['icon' => $tab['icon'], 'active' => $activeTab === $key])
                            </div>
                            <span class="truncate">{{ $tab['label'] }}</span>
                            @if($activeTab === $key)
                            <div class="absolute right-3 w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>

                <!-- System Section -->
                <div>
                    <div class="px-3 mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">System</span>
                    </div>
                    <div class="space-y-1">
                        @foreach(['health' => $tabs['health'], 'logs' => $tabs['logs']] as $key => $tab)
                        <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                           class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-300 {{ $activeTab === $key ? 'bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white shadow-lg shadow-slate-900/25' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300 {{ $activeTab === $key ? 'bg-white/20' : 'bg-slate-100 group-hover:bg-slate-200' }}">
                                @include('admin.settings.partials.tab-icon', ['icon' => $tab['icon'], 'active' => $activeTab === $key])
                            </div>
                            <span class="truncate">{{ $tab['label'] }}</span>
                            @if($activeTab === $key)
                            <div class="absolute right-3 w-1.5 h-1.5 rounded-full bg-white animate-pulse"></div>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
            </nav>

            <!-- Footer with version info -->
            <div class="px-4 py-3 border-t border-slate-100 bg-gradient-to-br from-slate-50/80 to-white">
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <span class="font-medium">ParcelMan v1.0</span>
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>All Systems OK</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 min-w-0">
        <div class="bg-white/90 backdrop-blur-2xl rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-200/50 overflow-hidden">
            <!-- Header with gradient background -->
            <div class="relative px-6 py-5 border-b border-slate-100">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80"></div>
                <div class="relative flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center shadow-inner">
                            @include('admin.settings.partials.tab-icon', ['icon' => $tabs[$activeTab]['icon'], 'active' => false])
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">{{ $tabs[$activeTab]['label'] }}</h2>
                            <p class="text-sm text-slate-500 font-medium mt-0.5">
                                @if(in_array($activeTab, ['email-logs', 'sms-logs', 'otp-logs', 'admin-audit-logs', 'notification-logs']))
                                    View and search {{ strtolower($tabs[$activeTab]['label']) }}
                                @else
                                    Configure your {{ strtolower($tabs[$activeTab]['label']) }} settings
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($activeTab === 'notification-logs')
                    {{-- Notification Logs header controls (View, Export, Total) --}}
                    <div x-data class="flex flex-wrap items-center gap-3">
                        {{-- View (column visibility) --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6M4 18h6M14 6h6M14 18h6M4 12h16"/>
                                </svg>
                                View
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                                 style="display: none;">
                                <template x-for="col in $store.notifLogs?.columns || []" :key="col.key">
                                    <button type="button" @@click="$dispatch('notif-toggle-column', col.key)"
                                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70">
                                        <span x-text="col.label"></span>
                                        <svg x-show="$store.notifLogs?.visibleColumns?.[col.key]" class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Export --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @@click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @@click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200/70 bg-white/85 backdrop-blur-xl shadow-2xl p-2 z-50"
                                 style="display: none;">
                                <button type="button" @@click="$dispatch('notif-export-csv'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    CSV
                                </button>
                                <div class="border-t border-slate-200/50 my-1"></div>
                                <button type="button" @@click="$dispatch('notif-print'); open = false" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-white/70 transition-colors">
                                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Print
                                </button>
                            </div>
                        </div>

                        {{-- Total count badge --}}
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700" x-text="($store.notifLogs?.total || 0) + ' Total Logs'"></span>
                    </div>
                    @elseif(!in_array($activeTab, ['health', 'logs', 'email-logs', 'sms-logs', 'otp-logs', 'admin-audit-logs', 'email-templates']))
                    <button type="button"
                            @@click="saveSettings()"
                            :disabled="saving"
                            class="group relative inline-flex items-center gap-2.5 px-5 py-2.5 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-slate-900/25 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                        <template x-if="!saving">
                            <svg class="w-4 h-4 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <template x-if="saving">
                            <svg class="w-4 h-4 animate-spin relative" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span class="relative" x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                    @endif
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Success/Error Messages -->
                <div x-show="message" x-cloak
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="mb-6 p-4 rounded-2xl shadow-lg"
                     :class="messageType === 'success' ? 'bg-gradient-to-r from-emerald-50 to-emerald-100/80 border border-emerald-200/70 text-emerald-800' : 'bg-gradient-to-r from-red-50 to-red-100/80 border border-red-200/70 text-red-800'">
                    <div class="flex items-center gap-3">
                        <template x-if="messageType === 'success'">
                            <div class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </template>
                        <template x-if="messageType === 'error'">
                            <div class="w-8 h-8 rounded-full bg-red-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                        </template>
                        <span class="text-sm font-semibold" x-text="message"></span>
                    </div>
                </div>

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
    testEmailEndpoint: @json(route('admin.settings.test-email')),
    testSmsEndpoint: @json(route('admin.settings.test-sms')),
    clearCacheEndpoint: @json(route('admin.settings.clear-cache')),
    activeTab: @json($activeTab),
    csrfToken: @json(csrf_token())
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
                    this.settings[key] = result.path;
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
