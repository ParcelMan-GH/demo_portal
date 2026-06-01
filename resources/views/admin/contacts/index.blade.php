@extends('admin.layouts.app')

@section('title', 'Contact Queue')
@section('breadcrumb-parent', 'Operations')
@section('breadcrumb-current', 'Contact Queue')

@section('content')

@php
    $contactConfig = [
        'dataUrl' => route('admin.contacts.data'),
        'assignUrl' => route('admin.contacts.assign', ['task' => '__TASK__']),
        'autoAssignUrl' => route('admin.contacts.auto-assign'),
        'logCallUrl' => route('admin.contacts.log-call', ['task' => '__TASK__']),
        'sendCodeUrl' => route('admin.contacts.send-code', ['task' => '__TASK__']),
        'resolveUrl' => route('admin.contacts.resolve', ['task' => '__TASK__']),
        'attemptsUrl' => route('admin.contacts.attempts', ['task' => '__TASK__']),
        'workerStatsUrl' => route('admin.contacts.worker-stats'),
    ];
@endphp

<div class="space-y-6"
     data-config='@json($contactConfig)'
     x-data="{
        config: JSON.parse($el.dataset.config),
        csrf: document.querySelector('meta[name=csrf-token]').content,
        tasks: [],
        meta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
        loading: false,
        search: '',
        statusFilter: '',
        statusFilterName: '',
        warehouseFilter: '',
        warehouseFilterName: '',
        workerFilter: '',
        workerFilterName: '',

        /* Modals */
        logCallOpen: false,
        logCallTask: null,
        logCallOutcome: '',
        logCallNotes: '',
        logCallSubmitting: false,

        resolveOpen: false,
        resolveTask: null,
        resolveOutcome: '',
        resolveNotes: '',
        resolveCallbackAt: '',
        resolveSubmitting: false,

        /* Confirmation code (for deliver/self_pickup) */
        resolveCode: '',
        resolveCodeError: '',
        codeSending: false,
        codeSentAt: null,
        codeExpiresAt: null,
        codeResendAfter: 0,
        codeCountdownTimer: null,

        historyOpen: false,
        historyTask: null,
        historyAttempts: [],
        historyLoading: false,

        assignDropdownTask: null,

	        workerStatsOpen: false,
	        workerStatsData: [],
	        workerStatsLoading: false,

	        autoAssignWarehouse: '',
	        autoAssigning: false,

	        assigningTaskId: null,
	        assignmentNotice: null,
	        assignmentNoticeTimeout: null,

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.meta.current_page,
                    per_page: this.meta.per_page,
                });
                if (this.search) params.append('search', this.search);
                if (this.statusFilter) params.append('status', this.statusFilter);
                if (this.warehouseFilter) params.append('warehouse_id', this.warehouseFilter);
                if (this.workerFilter) params.append('worker_id', this.workerFilter);

                const res = await fetch(this.config.dataUrl + '?' + params.toString());
                const json = await res.json();
                this.tasks = json.data || [];
                this.meta = json.meta || this.meta;
            } catch (e) {
                console.error('Failed to load contact tasks', e);
            } finally {
                this.loading = false;
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.meta.last_page) return;
            this.meta.current_page = page;
            this.loadData();
        },

        /* Assign */
        toggleAssignDropdown(taskId) {
            this.assignDropdownTask = this.assignDropdownTask === taskId ? null : taskId;
        },

	        async assignTask(task, workerId, workerName = '') {
	            this.assignDropdownTask = null;
	            this.assigningTaskId = task.id;
	            const fallbackName = workerName || '';
	            this.setAssignmentNotice(fallbackName ? `Assigning to ${fallbackName}...` : 'Assigning contact task...', 'info', false);
	            try {
	                const url = this.config.assignUrl.replace('__TASK__', task.id);
	                const res = await fetch(url, {
	                    method: 'POST',
	                    headers: {
	                        'Content-Type': 'application/json',
	                        'Accept': 'application/json',
	                        'X-Requested-With': 'XMLHttpRequest',
	                        'X-CSRF-TOKEN': this.csrf,
	                    },
	                    body: JSON.stringify({ user_id: workerId, worker_id: workerId }),
	                });
	                const json = await res.json().catch(() => ({}));
	                if (res.ok && json.success) {
	                    const updatedTask = json.task || {
	                        ...task,
	                        assigned_to_id: workerId,
	                        assigned_to_name: fallbackName,
	                        assigned_to: fallbackName,
	                        status: task.status === 'pending' ? 'assigned' : task.status,
	                    };
	                    this.tasks = this.tasks.map((row) => String(row.id) === String(task.id) ? updatedTask : row);
	                    const message = json.message || 'Task assigned successfully';
	                    this.setAssignmentNotice(message, 'success');
	                    window.showToast?.(message, 'success');
	                } else {
	                    const message = json.message || `Failed to assign. Server returned ${res.status}.`;
	                    this.setAssignmentNotice(message, 'error');
	                    window.showToast?.(message, 'error');
	                }
	            } catch (e) {
	                console.error('Failed to assign contact task', e);
	                const message = 'Network error while assigning. Please try again.';
	                this.setAssignmentNotice(message, 'error');
	                window.showToast?.(message, 'error');
	            } finally {
	                this.assigningTaskId = null;
	            }
	        },

        /* Auto-Assign */
        async autoAssign() {
            if (!this.autoAssignWarehouse) {
                window.showToast?.('Please select a warehouse first', 'error');
                return;
            }
            this.autoAssigning = true;
            try {
                const res = await fetch(this.config.autoAssignUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ warehouse_id: this.autoAssignWarehouse }),
                });
                const json = await res.json();
                if (res.ok) {
                    window.showToast?.(json.message || 'Auto-assign complete', 'success');
                    this.loadData();
                } else {
                    window.showToast?.(json.message || 'Auto-assign failed', 'error');
                }
            } catch (e) {
                window.showToast?.('Network error', 'error');
            } finally {
                this.autoAssigning = false;
            }
        },

        /* Log Call Modal */
        openLogCall(task) {
            this.logCallTask = task;
            this.logCallOutcome = '';
            this.logCallNotes = '';
            this.logCallSubmitting = false;
            this.logCallOpen = true;
        },

        async submitLogCall() {
            if (!this.logCallOutcome) return;
            this.logCallSubmitting = true;
            try {
                const url = this.config.logCallUrl.replace('__TASK__', this.logCallTask.id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ outcome: this.logCallOutcome, notes: this.logCallNotes }),
                });
                if (res.ok) {
                    window.showToast?.('Call logged successfully', 'success');
                    this.logCallOpen = false;
                    this.loadData();
                } else {
                    const err = await res.json();
                    window.showToast?.(err.message || 'Failed to log call', 'error');
                }
            } catch (e) {
                window.showToast?.('Network error', 'error');
            } finally {
                this.logCallSubmitting = false;
            }
        },

        /* Resolve Modal */
        requiresVerification(outcome) {
            return outcome === 'deliver' || outcome === 'self_pickup';
        },

        openResolve(task) {
            this.resolveTask = task;
            this.resolveOutcome = '';
            this.resolveNotes = '';
            this.resolveCallbackAt = '';
            this.resolveSubmitting = false;
            this.resetCodeState();
            this.resolveOpen = true;
        },

        resetCodeState() {
            this.resolveCode = '';
            this.resolveCodeError = '';
            this.codeSending = false;
            this.codeSentAt = null;
            this.codeExpiresAt = null;
            this.codeResendAfter = 0;
            if (this.codeCountdownTimer) {
                clearInterval(this.codeCountdownTimer);
                this.codeCountdownTimer = null;
            }
        },

        startResendCountdown(seconds) {
            this.codeResendAfter = seconds;
            if (this.codeCountdownTimer) clearInterval(this.codeCountdownTimer);
            this.codeCountdownTimer = setInterval(() => {
                this.codeResendAfter = Math.max(0, this.codeResendAfter - 1);
                if (this.codeResendAfter === 0) {
                    clearInterval(this.codeCountdownTimer);
                    this.codeCountdownTimer = null;
                }
            }, 1000);
        },

        async sendConfirmationCode() {
            if (!this.resolveTask || this.codeSending || this.codeResendAfter > 0) return;
            this.codeSending = true;
            this.resolveCodeError = '';
            try {
                const url = this.config.sendCodeUrl.replace('__TASK__', this.resolveTask.id);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success) {
                    this.codeSentAt = json.data?.sent_at ?? new Date().toISOString();
                    this.codeExpiresAt = json.data?.expires_at ?? null;
                    this.startResendCountdown(json.data?.resend_after_seconds ?? 60);
                    window.showToast?.(json.message || 'Code sent to recipient', 'success');
                } else {
                    // Throttled resend still returns resend_after_seconds; restart the timer.
                    if (json.data?.resend_after_seconds) {
                        this.startResendCountdown(json.data.resend_after_seconds);
                    }
                    window.showToast?.(json.message || 'Failed to send code', 'error');
                }
            } catch (e) {
                window.showToast?.('Network error', 'error');
            } finally {
                this.codeSending = false;
            }
        },

        async submitResolve() {
            if (!this.resolveOutcome) return;
            if (this.requiresVerification(this.resolveOutcome) && !this.resolveCode.trim()) {
                this.resolveCodeError = 'Enter the code the recipient read to you.';
                return;
            }
            this.resolveSubmitting = true;
            this.resolveCodeError = '';
            try {
                const url = this.config.resolveUrl.replace('__TASK__', this.resolveTask.id);
                const body = { outcome: this.resolveOutcome, notes: this.resolveNotes };
                if (this.resolveOutcome === 'callback' && this.resolveCallbackAt) {
                    body.callback_at = this.resolveCallbackAt;
                }
                if (this.requiresVerification(this.resolveOutcome)) {
                    body.confirmation_code = this.resolveCode.trim().toUpperCase();
                }
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify(body),
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success !== false) {
                    window.showToast?.('Task resolved successfully', 'success');
                    this.resolveOpen = false;
                    this.resetCodeState();
                    this.loadData();
                } else {
                    // Verification failures come back as 422 with { code: 'invalid' | 'expired' | 'missing' | 'exhausted' }
                    if (json.code) {
                        this.resolveCodeError = json.message || 'Code could not be verified.';
                    } else {
                        window.showToast?.(json.message || 'Failed to resolve', 'error');
                    }
                }
            } catch (e) {
                window.showToast?.('Network error', 'error');
            } finally {
                this.resolveSubmitting = false;
            }
        },

        /* Call History Modal */
        async openHistory(task) {
            this.historyTask = task;
            this.historyAttempts = [];
            this.historyLoading = true;
            this.historyOpen = true;
            try {
                const url = this.config.attemptsUrl.replace('__TASK__', task.id);
                const res = await fetch(url);
                const json = await res.json();
                this.historyAttempts = json.data || [];
            } catch (e) {
                console.error('Failed to load attempts', e);
            } finally {
                this.historyLoading = false;
            }
        },

        /* Worker Stats Modal */
        async openWorkerStats() {
            this.workerStatsData = [];
            this.workerStatsLoading = true;
            this.workerStatsOpen = true;
            try {
                const res = await fetch(this.config.workerStatsUrl);
                const json = await res.json();
                this.workerStatsData = json.data || [];
            } catch (e) {
                console.error('Failed to load worker stats', e);
            } finally {
                this.workerStatsLoading = false;
            }
        },

	        /* Helpers */
	        assignedName(task) {
	            return task.assigned_to_name || task.assigned_to || '';
	        },

	        assignedInitial(task) {
	            const name = this.assignedName(task);
	            return name ? name.charAt(0).toUpperCase() : '?';
	        },

	        setAssignmentNotice(message, type = 'success', autoDismiss = true) {
	            if (this.assignmentNoticeTimeout) {
	                clearTimeout(this.assignmentNoticeTimeout);
	                this.assignmentNoticeTimeout = null;
	            }
	            this.assignmentNotice = { message, type };
	            if (autoDismiss && type !== 'error') {
	                this.assignmentNoticeTimeout = setTimeout(() => {
	                    this.assignmentNotice = null;
	                    this.assignmentNoticeTimeout = null;
	                }, 5000);
	            }
	        },

	        clearAssignmentNotice() {
	            if (this.assignmentNoticeTimeout) {
	                clearTimeout(this.assignmentNoticeTimeout);
	                this.assignmentNoticeTimeout = null;
	            }
	            this.assignmentNotice = null;
	        },

	        statusBadgeClass(status) {
            const map = {
                pending: 'bg-slate-100 text-slate-700 border-slate-200',
                assigned: 'bg-blue-50 text-blue-700 border-blue-200',
                in_progress: 'bg-indigo-50 text-indigo-700 border-indigo-200',
                resolved: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            };
            return map[status] || 'bg-slate-100 text-slate-700 border-slate-200';
        },

        outcomeBadgeClass(outcome) {
            const map = {
                deliver: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                self_pickup: 'bg-amber-50 text-amber-700 border-amber-200',
                unreachable: 'bg-rose-50 text-rose-700 border-rose-200',
                wrong_number: 'bg-red-50 text-red-700 border-red-200',
                callback: 'bg-violet-50 text-violet-700 border-violet-200',
            };
            return map[outcome] || 'bg-slate-100 text-slate-700 border-slate-200';
        },

        deliveryMarkerBadgeClass(type) {
            const map = {
                rider: 'bg-blue-50 text-blue-700 border-blue-200',
                agent: 'bg-orange-50 text-orange-700 border-orange-200',
                public: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            };
            return map[type] || 'bg-slate-100 text-slate-600 border-slate-200';
        },

        formatStatus(s) {
            return s ? s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-';
        },
     }"
>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Unassigned -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['unassigned'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Unassigned</p>
            </div>
        </div>

        <!-- Assigned -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['assigned'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Assigned</p>
            </div>
        </div>

        <!-- In Progress -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['in_progress'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">In Progress</p>
            </div>
        </div>

        <!-- Callbacks Due -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-50 to-violet-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['callbacks_due'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Callbacks Due</p>
            </div>
        </div>

        <!-- Resolved Today -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg p-5 flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['resolved_today'] ?? 0) }}</p>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Resolved Today</p>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/80 shadow-lg shadow-slate-300/40 ring-1 ring-slate-100">
        <!-- Card Header -->
        <div class="px-6 py-5 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-100">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Contact Queue</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Manage recipient contact tasks across all warehouses</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700" x-text="meta.total + ' Total'"></span>
            </div>
        </div>

	        <!-- Toolbar -->
	        <div class="p-6 pb-0">
	            <div x-show="assignmentNotice" x-transition
	                 class="mb-4 rounded-2xl border px-4 py-3 text-sm font-medium flex items-start justify-between gap-3"
	                 :class="assignmentNotice && assignmentNotice.type === 'error'
	                    ? 'border-rose-200 bg-rose-50 text-rose-700'
	                    : (assignmentNotice && assignmentNotice.type === 'info'
	                        ? 'border-blue-200 bg-blue-50 text-blue-700'
	                        : 'border-emerald-200 bg-emerald-50 text-emerald-700')"
	                 style="display: none;">
	                <span x-text="assignmentNotice ? assignmentNotice.message : ''"></span>
	                <button type="button" @@click="clearAssignmentNotice()" class="shrink-0 text-xs font-semibold opacity-70 hover:opacity-100">Dismiss</button>
	            </div>
	            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <!-- Filters -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <!-- Search -->
                    <div class="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            x-model="search"
                            @@input.debounce.500ms="meta.current_page = 1; loadData()"
                            placeholder="Search shipment, tracking, recipient..."
                            class="w-full px-3 py-2 pr-10 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 text-sm text-slate-900 placeholder-slate-400 transition-colors"
                        >
                        <svg class="absolute right-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Status Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-44">
                        <button type="button" @@click="open = !open"
                                class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                            <span x-text="statusFilterName || 'All statuses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl" style="display: none;">
                            <button type="button" @@click="statusFilter = ''; statusFilterName = ''; meta.current_page = 1; loadData(); open = false"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="statusFilter === '' ? 'bg-white/70 shadow-sm' : ''">
                                <svg x-show="statusFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All statuses</span>
                            </button>
                            @foreach(['pending' => 'Pending', 'assigned' => 'Assigned', 'in_progress' => 'In Progress', 'resolved' => 'Resolved'] as $val => $label)
                            <button type="button" @@click="statusFilter = '{{ $val }}'; statusFilterName = '{{ $label }}'; meta.current_page = 1; loadData(); open = false"
                                    class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="statusFilter === '{{ $val }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">
                                <svg x-show="statusFilter === '{{ $val }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $label }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Warehouse Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-48">
                        <button type="button" @@click="open = !open"
                                class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                            <span x-text="warehouseFilterName || 'All warehouses'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-64 overflow-y-auto" style="display: none;">
                            <button type="button" @@click="warehouseFilter = ''; warehouseFilterName = ''; meta.current_page = 1; loadData(); open = false"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="warehouseFilter === '' ? 'bg-white/70 shadow-sm' : ''">
                                <svg x-show="warehouseFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All warehouses</span>
                            </button>
                            @foreach($warehouses as $warehouse)
                            <button type="button" @@click="warehouseFilter = '{{ $warehouse->id }}'; warehouseFilterName = '{{ $warehouse->name }}'; meta.current_page = 1; loadData(); open = false"
                                    class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="warehouseFilter === '{{ $warehouse->id }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">
                                <svg x-show="warehouseFilter === '{{ $warehouse->id }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $warehouse->name }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Worker Filter -->
                    <div x-data="{ open: false }" class="relative w-full sm:w-48">
                        <button type="button" @@click="open = !open"
                                class="w-full inline-flex items-center justify-between px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors">
                            <span x-text="workerFilterName || 'All workers'"></span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @@click.away="open = false" x-transition
                             class="absolute left-0 mt-2 w-full rounded-2xl border border-slate-200/70 bg-white/85 shadow-2xl p-2 z-50 backdrop-blur-xl max-h-64 overflow-y-auto" style="display: none;">
                            <button type="button" @@click="workerFilter = ''; workerFilterName = ''; meta.current_page = 1; loadData(); open = false"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="workerFilter === '' ? 'bg-white/70 shadow-sm' : ''">
                                <svg x-show="workerFilter === ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>All workers</span>
                            </button>
                            @foreach($workers as $worker)
                            <button type="button" @@click="workerFilter = '{{ $worker->id }}'; workerFilterName = '{{ $worker->name }}'; meta.current_page = 1; loadData(); open = false"
                                    class="mt-1 w-full flex items-center gap-2 px-3 py-2 rounded-full text-sm font-semibold text-slate-700 hover:bg-white/70"
                                    :class="workerFilter === '{{ $worker->id }}' ? 'bg-white/70 shadow-sm ring-1 ring-slate-200/60' : ''">
                                <svg x-show="workerFilter === '{{ $worker->id }}'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>{{ $worker->name }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right Controls -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Auto-Assign -->
                    <div class="flex items-center gap-2">
                        <select x-model="autoAssignWarehouse"
                                class="px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm text-slate-700 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                            <option value="">Select warehouse...</option>
                            @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" @@click="autoAssign()" :disabled="autoAssigning || !autoAssignWarehouse"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!autoAssigning" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <svg x-show="autoAssigning" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Auto-Assign
                        </button>
                    </div>

                    <!-- Worker Stats -->
                    <button type="button" @@click="openWorkerStats()"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200/70 rounded-xl bg-white/70 backdrop-blur-sm text-sm font-semibold text-slate-700 shadow-sm hover:bg-white/90 transition-colors">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Worker Stats
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200/60">
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Shipment #</th>
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Tracking</th>
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Recipient</th>
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Town</th>
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Warehouse</th>
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Assigned To</th>
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Status</th>
                            <th class="text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Attempts</th>
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Outcome</th>
                            <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Delivered By</th>
                            <th class="text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading -->
                        <template x-if="loading">
                            <tr>
                                <td colspan="11" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="w-8 h-8 text-slate-300 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <span class="text-sm text-slate-400">Loading tasks...</span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!loading && tasks.length === 0">
                            <tr>
                                <td colspan="11" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center">
                                            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">No contact tasks found</p>
                                        <p class="text-xs text-slate-400">Adjust your filters or check back later</p>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Data Rows -->
                        <template x-for="task in tasks" :key="task.id">
                            <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                                <!-- Shipment # -->
                                <td class="py-3 px-3">
                                    <span class="text-sm font-semibold text-slate-900" x-text="task.shipment_number || '-'"></span>
                                </td>
                                <!-- Tracking -->
                                <td class="py-3 px-3">
                                    <span class="text-sm text-slate-600 font-mono" x-text="task.tracking_number || task.tracking_code || '-'"></span>
                                </td>
                                <!-- Recipient -->
                                <td class="py-3 px-3">
                                    <div>
                                        <span class="text-sm font-medium text-slate-900" x-text="task.recipient_name || '-'"></span>
                                        <template x-if="task.recipient_phone">
                                            <a :href="'tel:' + task.recipient_phone" class="block text-xs text-indigo-600 hover:text-indigo-700 font-medium mt-0.5" x-text="task.recipient_phone"></a>
                                        </template>
                                    </div>
                                </td>
                                <!-- Town -->
                                <td class="py-3 px-3">
                                    <span class="text-sm text-slate-600" x-text="task.recipient_town || task.delivery_town || task.town || '-'"></span>
                                </td>
                                <!-- Warehouse -->
                                <td class="py-3 px-3">
                                    <span class="text-sm text-slate-600" x-text="task.warehouse_name || '-'"></span>
                                </td>
	                                <!-- Assigned To -->
	                                <td class="py-3 px-3">
	                                    <div class="space-y-1">
	                                        <template x-if="assignedName(task)">
	                                            <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
	                                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-[10px]" x-text="assignedInitial(task)"></span>
	                                                <span x-text="assignedName(task)"></span>
	                                            </div>
	                                        </template>
	                                        <template x-if="!assignedName(task)">
	                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-500">Unassigned</span>
	                                        </template>
	                                        <template x-if="task.assigned_at">
	                                            <p class="text-[11px] text-slate-400" x-text="'Assigned ' + task.assigned_at"></p>
	                                        </template>
                                    </div>
                                </td>
                                <!-- Status -->
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border"
                                          :class="statusBadgeClass(task.status)"
                                          x-text="formatStatus(task.status)"></span>
                                </td>
                                <!-- Attempts -->
                                <td class="py-3 px-3 text-center">
                                    <button @@click="openHistory(task)" class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors" x-text="task.attempts_count || 0"></button>
                                </td>
                                <!-- Outcome -->
                                <td class="py-3 px-3">
                                    <template x-if="task.outcome">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border"
                                              :class="outcomeBadgeClass(task.outcome)"
                                              x-text="formatStatus(task.outcome)"></span>
                                    </template>
                                    <template x-if="!task.outcome">
                                        <span class="text-xs text-slate-400">-</span>
                                    </template>
                                </td>
                                <!-- Delivered By -->
                                <td class="py-3 px-3 min-w-[150px]">
                                    <template x-if="task.delivered_by">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold"
                                                  :class="deliveryMarkerBadgeClass(task.delivered_by.type)"
                                                  x-text="task.delivered_by.type_label"></span>
                                            <p class="text-sm font-semibold text-slate-900" x-text="task.delivered_by.name || '-'"></p>
                                            <template x-if="task.delivered_by.phone">
                                                <p class="text-[11px] text-slate-500" x-text="task.delivered_by.phone"></p>
                                            </template>
                                            <template x-if="task.delivered_by.at">
                                                <p class="text-[11px] text-slate-400" x-text="task.delivered_by.at"></p>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!task.delivered_by">
                                        <span class="text-xs text-slate-400">-</span>
                                    </template>
                                </td>
                                <!-- Actions -->
                                <td class="py-3 px-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Assign -->
	                                        <div class="relative">
	                                            <button @@click="toggleAssignDropdown(task.id)"
	                                                    :disabled="assigningTaskId === task.id"
	                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-colors"
	                                                    :class="[
	                                                        task.assigned_to_id ? 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
	                                                        assigningTaskId === task.id ? 'opacity-60 cursor-wait' : ''
	                                                    ]">
	                                                <svg x-show="assigningTaskId !== task.id" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
	                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
	                                                </svg>
	                                                <svg x-show="assigningTaskId === task.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24" style="display: none;">
	                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
	                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
	                                                </svg>
	                                                <span x-text="assigningTaskId === task.id ? 'Assigning...' : (task.assigned_to_id ? 'Reassign' : 'Assign')"></span>
	                                            </button>
	                                            <div x-show="assignDropdownTask === task.id" @@click.away="assignDropdownTask = null" x-transition
	                                                 class="absolute right-0 mt-1 w-48 rounded-xl border border-slate-200/70 bg-white/95 shadow-xl p-1.5 z-50 backdrop-blur-xl max-h-48 overflow-y-auto" style="display: none;">
	                                                @foreach($workers as $worker)
	                                                <button type="button" @@click="assignTask(task, {{ $worker->id }}, @js($worker->name))"
	                                                        :disabled="assigningTaskId === task.id"
	                                                        class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors disabled:opacity-60 disabled:cursor-wait"
	                                                        :class="Number(task.assigned_to_id) === {{ $worker->id }} ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-100'">
	                                                    <span class="flex min-w-0 items-center gap-2">
	                                                        <span class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-600">{{ strtoupper(substr($worker->name, 0, 1)) }}</span>
	                                                        <span class="truncate">{{ $worker->name }}</span>
	                                                    </span>
	                                                    <span x-show="Number(task.assigned_to_id) === {{ $worker->id }}" class="text-[10px] font-bold" style="display: none;">Current</span>
	                                                </button>
	                                                @endforeach
	                                            </div>
                                        </div>

                                        <!-- Log Call -->
                                        <button @@click="openLogCall(task)" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            Call
                                        </button>

                                        <!-- Resolve -->
                                        <button @@click="openResolve(task)" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Resolve
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <template x-if="meta.last_page > 1">
                <div class="flex items-center justify-between pt-5 border-t border-slate-200/50 mt-4">
                    <p class="text-sm text-slate-500">
                        Showing <span class="font-medium text-slate-700" x-text="meta.from || 0"></span> to <span class="font-medium text-slate-700" x-text="meta.to || 0"></span> of <span class="font-medium text-slate-700" x-text="meta.total"></span> results
                    </p>
                    <div class="flex items-center gap-2">
                        <button @@click="goToPage(meta.current_page - 1)" :disabled="meta.current_page <= 1"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200/70 bg-white/70 text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Prev
                        </button>
                        <span class="text-sm font-medium text-slate-600">Page <span x-text="meta.current_page"></span> of <span x-text="meta.last_page"></span></span>
                        <button @@click="goToPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200/70 bg-white/70 text-sm font-medium text-slate-700 hover:bg-white/90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            Next
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- Log Call Modal -->
    <template x-teleport="body">
        <div x-show="logCallOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" style="display: none;">
            <div @@click.away="logCallOpen = false" x-show="logCallOpen" x-transition
                 class="w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Log Call</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5" x-text="logCallTask ? logCallTask.recipient_name + ' - ' + (logCallTask.recipient_phone || '') : ''"></p>
                            </div>
                        </div>
                        <button @@click="logCallOpen = false" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Body -->
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-3">Call Outcome</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach([
                                'answered' => ['Answered', 'emerald'],
                                'no_answer' => ['No Answer', 'slate'],
                                'busy' => ['Busy', 'amber'],
                                'wrong_number' => ['Wrong Number', 'red'],
                                'voicemail' => ['Voicemail', 'violet'],
                            ] as $val => [$label, $color])
                            <button type="button" @@click="logCallOutcome = '{{ $val }}'"
                                    class="px-3 py-2.5 rounded-xl border text-xs font-semibold transition-all"
                                    :class="logCallOutcome === '{{ $val }}'
                                        ? 'bg-{{ $color }}-50 border-{{ $color }}-300 text-{{ $color }}-700 ring-2 ring-{{ $color }}-200'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                {{ $label }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Notes</label>
                        <textarea x-model="logCallNotes" rows="3" placeholder="Optional notes about the call..."
                                  class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors resize-none"></textarea>
                    </div>
                </div>
                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                    <button type="button" @@click="logCallOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @@click="submitLogCall()" :disabled="!logCallOutcome || logCallSubmitting"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="logCallSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Log Call
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Resolve Modal -->
    <template x-teleport="body">
        <div x-show="resolveOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" style="display: none;">
            <div @@click.away="resolveOpen = false" x-show="resolveOpen" x-transition
                 class="w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Resolve Task</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5" x-text="resolveTask ? resolveTask.recipient_name + ' - ' + (resolveTask.shipment_number || '') : ''"></p>
                            </div>
                        </div>
                        <button @@click="resolveOpen = false" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Body -->
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-3">Resolution Outcome</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <button type="button" @@click="resolveOutcome = 'deliver'"
                                    class="px-3 py-2.5 rounded-xl border text-xs font-semibold transition-all"
                                    :class="resolveOutcome === 'deliver'
                                        ? 'bg-emerald-50 border-emerald-300 text-emerald-700 ring-2 ring-emerald-200'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                Deliver
                            </button>
                            <button type="button" @@click="resolveOutcome = 'self_pickup'"
                                    class="px-3 py-2.5 rounded-xl border text-xs font-semibold transition-all"
                                    :class="resolveOutcome === 'self_pickup'
                                        ? 'bg-amber-50 border-amber-300 text-amber-700 ring-2 ring-amber-200'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                Self Pickup
                            </button>
                            <button type="button" @@click="resolveOutcome = 'unreachable'"
                                    class="px-3 py-2.5 rounded-xl border text-xs font-semibold transition-all"
                                    :class="resolveOutcome === 'unreachable'
                                        ? 'bg-rose-50 border-rose-300 text-rose-700 ring-2 ring-rose-200'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                Unreachable
                            </button>
                            <button type="button" @@click="resolveOutcome = 'wrong_number'"
                                    class="px-3 py-2.5 rounded-xl border text-xs font-semibold transition-all"
                                    :class="resolveOutcome === 'wrong_number'
                                        ? 'bg-red-50 border-red-300 text-red-700 ring-2 ring-red-200'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                Wrong Number
                            </button>
                            <button type="button" @@click="resolveOutcome = 'callback'"
                                    class="px-3 py-2.5 rounded-xl border text-xs font-semibold transition-all"
                                    :class="resolveOutcome === 'callback'
                                        ? 'bg-violet-50 border-violet-300 text-violet-700 ring-2 ring-violet-200'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                Callback
                            </button>
                        </div>
                    </div>

                    <!-- Callback datetime (shown only for callback outcome) -->
                    <div x-show="resolveOutcome === 'callback'" x-transition>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Callback Date & Time</label>
                        <input type="datetime-local" x-model="resolveCallbackAt"
                               class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 text-sm text-slate-900 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors">
                    </div>

                    <!-- Recipient confirmation code (deliver / self_pickup only) -->
                    <div x-show="requiresVerification(resolveOutcome)" x-transition
                         class="rounded-2xl border border-emerald-200/80 bg-emerald-50/40 p-4">
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/90 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-emerald-900">Confirm with recipient</p>
                                <p class="text-[11px] text-emerald-800/80 mt-0.5">
                                    Send a code to the recipient, ask them to read it back on the call, then enter it below.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mb-3">
                            <button type="button" @@click="sendConfirmationCode()"
                                    :disabled="codeSending || codeResendAfter > 0"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="codeSending" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-show="!codeSentAt && !codeSending">Send Code</span>
                                <span x-show="codeSentAt && codeResendAfter === 0 && !codeSending">Resend Code</span>
                                <span x-show="codeResendAfter > 0" x-text="'Resend in ' + codeResendAfter + 's'"></span>
                                <span x-show="codeSending">Sending…</span>
                            </button>
                            <span x-show="codeSentAt" class="text-[11px] text-emerald-700">
                                Sent to <span class="font-mono" x-text="resolveTask?.recipient_phone"></span>
                            </span>
                        </div>

                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Code from recipient</label>
                        <input type="text" x-model="resolveCode" maxlength="10"
                               placeholder="e.g. XK7R4Q"
                               @@input="resolveCode = resolveCode.toUpperCase(); resolveCodeError = ''"
                               class="w-full px-3 py-2 border rounded-xl bg-white text-base font-mono tracking-[0.3em] uppercase text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-emerald-400/50 focus:border-emerald-300 transition-colors"
                               :class="resolveCodeError ? 'border-rose-300' : 'border-slate-200/70'">
                        <p x-show="resolveCodeError" x-text="resolveCodeError" class="mt-1.5 text-[11px] text-rose-600"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Notes</label>
                        <textarea x-model="resolveNotes" rows="3" placeholder="Optional resolution notes..."
                                  class="w-full px-3 py-2 border border-slate-200/70 rounded-xl bg-white/70 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-slate-400/50 focus:border-slate-300 transition-colors resize-none"></textarea>
                    </div>
                </div>
                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                    <button type="button" @@click="resolveOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @@click="submitResolve()" :disabled="!resolveOutcome || resolveSubmitting"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="resolveSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Resolve
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Call History Modal -->
    <template x-teleport="body">
        <div x-show="historyOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" style="display: none;">
            <div @@click.away="historyOpen = false" x-show="historyOpen" x-transition
                 class="w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Call History</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5" x-text="historyTask ? historyTask.recipient_name + ' - ' + (historyTask.shipment_number || '') : ''"></p>
                            </div>
                        </div>
                        <button @@click="historyOpen = false" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Body -->
                <div class="p-6 max-h-96 overflow-y-auto">
                    <!-- Loading -->
                    <div x-show="historyLoading" class="flex items-center justify-center py-8">
                        <svg class="w-6 h-6 text-slate-300 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                    <!-- Empty -->
                    <div x-show="!historyLoading && historyAttempts.length === 0" class="text-center py-8">
                        <p class="text-sm text-slate-400">No call attempts recorded yet.</p>
                    </div>
                    <!-- List -->
                    <div x-show="!historyLoading && historyAttempts.length > 0" class="space-y-3">
                        <template x-for="(attempt, idx) in historyAttempts" :key="idx">
                            <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="w-7 h-7 rounded-full bg-slate-200 flex items-center justify-center flex-shrink-0 text-[10px] font-bold text-slate-600" x-text="idx + 1"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-semibold text-slate-700" x-text="formatStatus(attempt.outcome)"></span>
                                        <span class="text-[10px] text-slate-400" x-text="attempt.created_at"></span>
                                    </div>
                                    <template x-if="attempt.admin_name">
                                        <p class="text-[11px] text-slate-500 mt-0.5">by <span class="font-medium" x-text="attempt.admin_name"></span></p>
                                    </template>
                                    <template x-if="attempt.notes">
                                        <p class="text-xs text-slate-600 mt-1" x-text="attempt.notes"></p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Worker Stats Modal -->
    <template x-teleport="body">
        <div x-show="workerStatsOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm" style="display: none;">
            <div @@click.away="workerStatsOpen = false" x-show="workerStatsOpen" x-transition
                 class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Worker Statistics</h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Performance overview for all contact workers</p>
                            </div>
                        </div>
                        <button @@click="workerStatsOpen = false" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Body -->
                <div class="p-6 max-h-[28rem] overflow-y-auto">
                    <!-- Loading -->
                    <div x-show="workerStatsLoading" class="flex items-center justify-center py-8">
                        <svg class="w-6 h-6 text-slate-300 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                    <!-- Table -->
                    <div x-show="!workerStatsLoading">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200/60">
                                    <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-2">Worker</th>
                                    <th class="text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-2">Warehouse</th>
                                    <th class="text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-2">Assigned</th>
                                    <th class="text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-2">In Progress</th>
                                    <th class="text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-2">Resolved</th>
                                    <th class="text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider pb-3 px-2">Calls Today</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="workerStatsData.length === 0">
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-sm text-slate-400">No worker statistics available.</td>
                                    </tr>
                                </template>
                                <template x-for="worker in workerStatsData" :key="worker.id">
                                    <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                                        <td class="py-2.5 px-2">
                                            <span class="text-sm font-medium text-slate-900" x-text="worker.name"></span>
                                        </td>
                                        <td class="py-2.5 px-2">
                                            <span class="text-sm text-slate-600" x-text="worker.warehouse_name || '-'"></span>
                                        </td>
                                        <td class="py-2.5 px-2 text-center">
                                            <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700" x-text="worker.assigned_count || 0"></span>
                                        </td>
                                        <td class="py-2.5 px-2 text-center">
                                            <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700" x-text="worker.in_progress_count || 0"></span>
                                        </td>
                                        <td class="py-2.5 px-2 text-center">
                                            <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700" x-text="worker.resolved_count || 0"></span>
                                        </td>
                                        <td class="py-2.5 px-2 text-center">
                                            <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700" x-text="worker.calls_today || 0"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>

@endsection
