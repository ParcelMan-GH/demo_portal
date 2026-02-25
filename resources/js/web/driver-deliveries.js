import { apiRequest, clearToken, getToken } from './auth-client';

const DELIVERY_TABS = [
    { key: 'all', label: 'All', statuses: [] },
    { key: 'assigned', label: 'Assigned', statuses: ['assigned'] },
    { key: 'out_for_delivery', label: 'Out for Delivery', statuses: ['out_for_delivery'] },
    { key: 'partially_delivered', label: 'Partially Delivered', statuses: ['partially_delivered'] },
    { key: 'completed', label: 'Completed', statuses: ['completed'] },
    { key: 'cancelled', label: 'Cancelled', statuses: ['cancelled'] },
];

function flattenErrors(payload) {
    const errors = payload?.errors;
    if (!errors || typeof errors !== 'object') return [];
    return Object.values(errors)
        .flatMap((v) => (Array.isArray(v) ? v : [v]))
        .filter((v) => typeof v === 'string' && v.trim() !== '');
}

function statusLabel(status) {
    if (!status) return '-';
    return String(status).split('_').map((t) => t.charAt(0).toUpperCase() + t.slice(1)).join(' ');
}

function formatDateTime(value) {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '-';
    return date.toLocaleString('en-US', {
        year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit',
    });
}

async function ensureDriverSessionOrRedirect() {
    const token = getToken('driver');
    if (!token) { window.location.href = '/driver/login'; return false; }
    const profile = await apiRequest('/api/v1/driver/profile', { role: 'driver' });
    if (!profile.success) { clearToken('driver'); window.location.href = '/driver/login'; return false; }
    return true;
}

export function driverDeliveriesListPage() {
    return {
        loading: true,
        alert: null,
        validationErrors: [],
        deliveries: [],
        activeTab: 'all',
        tabCounts: { all: 0, assigned: 0, out_for_delivery: 0, partially_delivered: 0, completed: 0, cancelled: 0 },
        filters: {
            search: '',
            status: [],
            limit: 15,
            offset: 0,
        },
        pagination: {
            offset: 0, limit: 15, total: 0, has_more: false,
            next_offset: null, current_page: 1, last_page: 1, per_page: 15,
        },

        async init() {
            if (!(await ensureDriverSessionOrRedirect())) return;
            await Promise.all([this.loadDeliveries(), this.loadTabCounts()]);
        },

        statusLabel,
        formatDateTime,

        statusColor(status) {
            const map = {
                assigned: 'blue',
                out_for_delivery: 'amber',
                partially_delivered: 'orange',
                completed: 'green',
                cancelled: 'red',
            };
            return map[status] || 'gray';
        },

        stopStatusColor(stop) {
            const map = { pending: 'gray', arrived: 'amber', delivered: 'green', failed: 'red' };
            return map[stop?.status] || 'gray';
        },

        stopStatusLabel(stop) {
            return statusLabel(stop?.status);
        },

        showAlert(type, message) { this.alert = { type, message }; },
        setValidationErrors(payload) { this.validationErrors = flattenErrors(payload); },
        clearValidationErrors() { this.validationErrors = []; },

        buildQueryString() {
            const params = new URLSearchParams();
            if (this.filters.search.trim()) params.append('search', this.filters.search.trim());
            (this.filters.status || []).forEach((s) => { if (s) params.append('status[]', s); });
            params.append('limit', String(this.filters.limit));
            params.append('offset', String(this.filters.offset));
            return params.toString();
        },

        async loadDeliveries() {
            this.loading = true;
            this.alert = null;
            this.clearValidationErrors();
            const result = await apiRequest(`/api/v1/driver/deliveries?${this.buildQueryString()}`, { role: 'driver' });
            if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                this.loading = false;
                return;
            }
            const data = result.payload?.data || {};
            this.deliveries = Array.isArray(data.runs) ? data.runs : (Array.isArray(data.deliveries) ? data.deliveries : []);
            this.pagination = data.pagination || this.pagination;
            this.filters.offset = Number(this.pagination.offset || 0);
            this.loading = false;
        },

        async loadTabCounts() {
            const statuses = ['assigned', 'out_for_delivery', 'partially_delivered', 'completed', 'cancelled'];
            const calls = statuses.map((s) =>
                apiRequest(`/api/v1/driver/deliveries?limit=1&offset=0&status[]=${s}`, { role: 'driver' })
            );
            const allResult = apiRequest('/api/v1/driver/deliveries?limit=1&offset=0', { role: 'driver' });
            const [all, ...perStatus] = await Promise.all([allResult, ...calls]);
            if (all.success) this.tabCounts.all = all.payload?.data?.pagination?.total || all.payload?.meta?.total || 0;
            statuses.forEach((s, i) => {
                if (perStatus[i]?.success) {
                    this.tabCounts[s] = perStatus[i].payload?.data?.pagination?.total || perStatus[i].payload?.meta?.total || 0;
                }
            });
        },

        async switchTab(key) {
            const tab = DELIVERY_TABS.find((t) => t.key === key);
            if (!tab) return;
            this.activeTab = key;
            this.filters.status = [...tab.statuses];
            this.filters.offset = 0;
            await this.loadDeliveries();
        },

        applyFilters() {
            this.filters.offset = 0;
            this.loadDeliveries();
        },

        resetFilters() {
            this.filters = { search: '', status: [], limit: 15, offset: 0 };
            this.activeTab = 'all';
            this.loadDeliveries();
        },

        async nextPage() {
            if (!this.pagination.has_more || this.pagination.next_offset === null) return;
            this.filters.offset = Number(this.pagination.next_offset);
            await this.loadDeliveries();
        },

        async previousPage() {
            if (this.filters.offset <= 0) return;
            this.filters.offset = Math.max(0, this.filters.offset - this.filters.limit);
            await this.loadDeliveries();
        },
    };
}

export function driverDeliveryShowPage() {
    return {
        loading: true,
        runId: null,
        run: null,
        alert: null,
        validationErrors: [],
        toasts: [],
        nextToastId: 1,
        actionLoading: false,
        stopActionLoading: {},
        stopForms: {},
        confirmDialog: { open: false, title: '', message: '', onYes: null },

        async init() {
            this.runId = this.$el.dataset.runId;
            if (!(await ensureDriverSessionOrRedirect())) return;
            await this.loadRun();
            this.loading = false;
        },

        statusLabel,
        formatDateTime,

        get heroClass() {
            const s = this.run?.status || '';
            if (s === 'completed') return 'sh-hero-completed';
            if (s === 'out_for_delivery') return 'sh-hero-in_transit';
            if (s === 'partially_delivered') return 'sh-hero-loading';
            if (s === 'cancelled') return 'sh-hero-no-progress';
            return '';
        },

        get heroMessage() {
            const s = this.run?.status || '';
            const msgs = {
                assigned:            { title: 'Ready for Delivery', text: 'You have been assigned this delivery run. Head out to start delivering.' },
                out_for_delivery:    { title: 'Out for Delivery', text: 'You are actively delivering. Work through each stop below.' },
                partially_delivered: { title: 'Partially Delivered', text: 'Some stops are complete. Keep going until all stops are delivered or marked.' },
                completed:           { title: 'Run Complete', text: 'All stops have been completed. Great work!' },
                cancelled:           { title: 'Run Cancelled', text: 'This delivery run has been cancelled.' },
            };
            return msgs[s] || { title: 'Delivery Run', text: 'View stops and take action on each delivery.' };
        },

        get progressSteps() {
            const statuses = ['assigned', 'out_for_delivery', 'completed'];
            const current = this.run?.status || '';
            const isCancelled = current === 'cancelled';
            const effectiveStatus = current === 'partially_delivered' ? 'out_for_delivery' : current;
            const currentIdx = statuses.indexOf(effectiveStatus);
            return statuses.map((s, i) => ({
                key: s,
                num: i + 1,
                label: s === 'out_for_delivery' ? 'Out for Delivery' : statusLabel(s),
                state: isCancelled ? 'sh-step-pending' : (i < currentIdx ? 'sh-step-done' : (i === currentIdx ? 'sh-step-active' : 'sh-step-pending')),
            }));
        },

        stopStatusColor(stop) {
            const map = { pending: 'gray', arrived: 'amber', delivered: 'green', failed: 'red' };
            return map[stop?.status] || 'gray';
        },

        stopStatusLabel(stop) {
            return statusLabel(stop?.status);
        },

        showAlert(type, message) { this.alert = { type, message }; this.pushToast(type, message); },
        setValidationErrors(payload) { this.validationErrors = flattenErrors(payload); },
        clearValidationErrors() { this.validationErrors = []; },

        pushToast(type, message, duration = 4500) {
            if (!message) return;
            const id = this.nextToastId++;
            this.toasts.push({ id, type, message });
            window.setTimeout(() => this.dismissToast(id), duration);
        },
        dismissToast(id) { this.toasts = this.toasts.filter((t) => t.id !== id); },

        confirm(message, onYes, title = 'Confirm Action') {
            this.confirmDialog = { open: true, title, message, onYes };
        },
        confirmYes() {
            if (this.confirmDialog?.onYes) this.confirmDialog.onYes();
            this.confirmDialog = { open: false, title: '', message: '', onYes: null };
        },
        confirmNo() {
            this.confirmDialog = { open: false, title: '', message: '', onYes: null };
        },

        async loadRun() {
            this.alert = null;
            this.clearValidationErrors();
            const result = await apiRequest(`/api/v1/driver/deliveries/${this.runId}`, { role: 'driver' });
            if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message || 'Failed to load delivery run.');
                return;
            }
            this.run = result.payload?.data?.run || result.payload?.data?.delivery || null;
        },

        getStopForm(stopId) {
            if (!this.stopForms[stopId]) {
                this.stopForms[stopId] = { verification_code: '', failure_reason: '', failure_notes: '', proof_photo_file: null };
            }
            return this.stopForms[stopId];
        },

        onProofPhotoSelected(stopId, event) {
            const file = event.target.files?.[0] || null;
            this.getStopForm(stopId).proof_photo_file = file;
        },

        async arriveAtStop(stop) {
            if (!stop?.id || stop.status !== 'pending') return;
            this.stopActionLoading[stop.id] = true;
            try {
                const result = await apiRequest(`/api/v1/driver/deliveries/${this.runId}/stops/${stop.id}/arrive`, { method: 'POST', role: 'driver' });
                if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
                if (!result.success) { this.setValidationErrors(result.payload); this.showAlert('error', result.message); return; }
                this.showAlert('success', result.message || 'Arrived at stop.');
                await this.loadRun();
            } catch { this.showAlert('error', 'Unable to record arrival right now.'); }
            finally { this.stopActionLoading[stop.id] = false; }
        },

        async confirmDelivery(stop) {
            const form = this.getStopForm(stop.id);
            const code = (form.verification_code || '').trim();
            if (!code) { this.showAlert('error', 'Please enter the verification code.'); return; }
            if (!stop?.id || stop.status !== 'arrived') return;
            this.stopActionLoading[stop.id] = true;
            try {
                const fd = new FormData();
                fd.append('verification_code', code);
                if (form.proof_photo_file) fd.append('proof_photo', form.proof_photo_file);
                const result = await apiRequest(`/api/v1/driver/deliveries/${this.runId}/stops/${stop.id}/confirm`, {
                    method: 'POST', role: 'driver', body: fd,
                });
                if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
                if (!result.success) { this.setValidationErrors(result.payload); this.showAlert('error', result.message); return; }
                this.showAlert('success', result.message || 'Delivery confirmed.');
                form.verification_code = '';
                form.proof_photo_file = null;
                await this.loadRun();
            } catch { this.showAlert('error', 'Unable to confirm delivery right now.'); }
            finally { this.stopActionLoading[stop.id] = false; }
        },

        async failDelivery(stop) {
            const form = this.getStopForm(stop.id);
            const reason = (form.failure_reason || '').trim();
            if (!reason) { this.showAlert('error', 'Please select a failure reason.'); return; }
            if (!stop?.id || stop.status !== 'arrived') return;
            this.stopActionLoading[stop.id] = true;
            try {
                const result = await apiRequest(`/api/v1/driver/deliveries/${this.runId}/stops/${stop.id}/fail`, {
                    method: 'POST', role: 'driver', data: { failure_reason: reason, failure_notes: form.failure_notes || '' },
                });
                if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
                if (!result.success) { this.setValidationErrors(result.payload); this.showAlert('error', result.message); return; }
                this.showAlert('success', result.message || 'Stop marked as failed.');
                form.failure_reason = '';
                form.failure_notes = '';
                await this.loadRun();
            } catch { this.showAlert('error', 'Unable to mark stop as failed right now.'); }
            finally { this.stopActionLoading[stop.id] = false; }
        },
    };
}

function registerDriverDeliveryComponents() {
    if (!window.Alpine) return;
    window.Alpine.data('driverDeliveriesListPage', driverDeliveriesListPage);
    window.Alpine.data('driverDeliveryShowPage', driverDeliveryShowPage);
}

if (window.Alpine) {
    registerDriverDeliveryComponents();
} else {
    document.addEventListener('alpine:init', registerDriverDeliveryComponents);
}
