import { apiRequest, clearToken, getToken } from './auth-client';

const TRANSPORT_TABS = [
    { key: 'all', label: 'All', statuses: [] },
    { key: 'assigned', label: 'Assigned', statuses: ['assigned'] },
    { key: 'loading', label: 'Loading', statuses: ['loading'] },
    { key: 'in_transit', label: 'In Transit', statuses: ['in_transit'] },
    { key: 'arrived', label: 'Arrived', statuses: ['arrived'] },
    { key: 'received', label: 'Received', statuses: ['received'] },
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
    return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
}

async function ensureDriverSessionOrRedirect() {
    const token = getToken('driver');
    if (!token) { window.location.href = '/driver/login'; return false; }
    const profile = await apiRequest('/api/v1/driver/profile', { role: 'driver' });
    if (!profile.success) { clearToken('driver'); window.location.href = '/driver/login'; return false; }
    return true;
}

export function driverTransportsListPage() {
    return {
        loading: true,
        alert: null,
        validationErrors: [],
        transports: [],
        activeTab: 'all',
        tabCounts: { all: 0, assigned: 0, loading: 0, in_transit: 0, arrived: 0, received: 0, cancelled: 0 },
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
            await Promise.all([this.loadTransports(), this.loadTabCounts()]);
        },

        statusLabel,
        formatDateTime,

        statusColor(status) {
            const map = {
                assigned: 'blue',
                loading: 'amber',
                in_transit: 'purple',
                arrived: 'cyan',
                received: 'green',
                cancelled: 'red',
            };
            return map[status] || 'gray';
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

        async loadTransports() {
            this.loading = true;
            this.alert = null;
            this.clearValidationErrors();
            const result = await apiRequest(`/api/v1/driver/transports?${this.buildQueryString()}`, { role: 'driver' });
            if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                this.loading = false;
                return;
            }
            const data = result.payload?.data || {};
            this.transports = Array.isArray(data.manifests) ? data.manifests : (Array.isArray(data.transports) ? data.transports : []);
            this.pagination = data.pagination || this.pagination;
            this.filters.offset = Number(this.pagination.offset || 0);
            this.loading = false;
        },

        async loadTabCounts() {
            const statuses = ['assigned', 'loading', 'in_transit', 'arrived', 'received', 'cancelled'];
            const calls = statuses.map((s) =>
                apiRequest(`/api/v1/driver/transports?limit=1&offset=0&status[]=${s}`, { role: 'driver' })
            );
            const allResult = apiRequest('/api/v1/driver/transports?limit=1&offset=0', { role: 'driver' });
            const [all, ...perStatus] = await Promise.all([allResult, ...calls]);
            if (all.success) this.tabCounts.all = all.payload?.data?.pagination?.total || all.payload?.meta?.total || 0;
            statuses.forEach((s, i) => {
                if (perStatus[i]?.success) {
                    this.tabCounts[s] = perStatus[i].payload?.data?.pagination?.total || perStatus[i].payload?.meta?.total || 0;
                }
            });
        },

        async switchTab(key) {
            const tab = TRANSPORT_TABS.find((t) => t.key === key);
            if (!tab) return;
            this.activeTab = key;
            this.filters.status = [...tab.statuses];
            this.filters.offset = 0;
            await this.loadTransports();
        },

        async nextPage() {
            if (!this.pagination.has_more || this.pagination.next_offset === null) return;
            this.filters.offset = Number(this.pagination.next_offset);
            await this.loadTransports();
        },

        async previousPage() {
            if (this.filters.offset <= 0) return;
            this.filters.offset = Math.max(0, this.filters.offset - this.filters.limit);
            await this.loadTransports();
        },

        async startLoading(transport) {
            if (!transport?.id || transport.status !== 'assigned') return;
            if (!window.confirm(`Start loading for manifest ${transport.manifest_number}?`)) return;
            const result = await apiRequest(`/api/v1/driver/transports/${transport.id}/start-loading`, { method: 'POST', role: 'driver' });
            if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
            if (!result.success) { this.showAlert('error', result.message); return; }
            this.showAlert('success', result.message || 'Loading started.');
            await Promise.all([this.loadTransports(), this.loadTabCounts()]);
        },
    };
}

export function driverTransportShowPage() {
    return {
        loading: true,
        manifestId: null,
        transport: null,
        alert: null,
        validationErrors: [],
        toasts: [],
        nextToastId: 1,
        actionLoading: false,
        scanLoading: false,
        scanForm: { tracking_code: '' },
        confirmDialog: null,

        async init() {
            this.manifestId = this.$el.dataset.manifestId;
            if (!(await ensureDriverSessionOrRedirect())) return;
            await this.loadTransport();
            this.loading = false;
        },

        statusLabel,
        formatDateTime,

        get canStartLoading() { return this.transport?.status === 'assigned'; },
        get canScanItems() { return this.transport?.status === 'loading'; },
        get canDepart() { return this.transport?.status === 'loading'; },
        get canArrive() { return this.transport?.status === 'in_transit'; },

        get heroClass() {
            const s = this.transport?.status || '';
            if (['arrived', 'received'].includes(s)) return 'sh-hero-arrived';
            if (s === 'in_transit') return 'sh-hero-in_transit';
            if (s === 'loading') return 'sh-hero-loading';
            if (s === 'cancelled') return 'no-progress';
            return '';
        },

        get heroMessage() {
            const s = this.transport?.status || '';
            const msgs = {
                assigned:   { title: 'Ready to Load', text: 'Tap "Start Loading" to begin scanning items onto your vehicle.' },
                loading:    { title: 'Loading In Progress', text: "Scan each item's tracking code to mark it as loaded. Tap Depart when done." },
                in_transit: { title: 'En Route', text: 'You are on your way to the destination warehouse. Tap Arrive when you reach it.' },
                arrived:    { title: 'Arrived at Destination', text: 'You have arrived. The warehouse team will receive and check in the items.' },
                received:   { title: 'Delivery Complete', text: 'All items have been received by the destination warehouse. Great work!' },
                cancelled:  { title: 'Manifest Cancelled', text: 'This transport manifest has been cancelled.' },
            };
            return msgs[s] || { title: 'Transport Manifest', text: 'View the manifest details and take action as needed.' };
        },

        get progressSteps() {
            const statuses = ['assigned', 'loading', 'in_transit', 'arrived', 'received'];
            const current = this.transport?.status || '';
            const isCancelled = current === 'cancelled';
            const currentIdx = statuses.indexOf(current);
            return {
                isCancelled,
                currentIdx: Math.max(0, currentIdx),
                total: statuses.length,
                steps: statuses.map((s, i) => ({
                    label: statusLabel(s),
                    state: isCancelled ? 'pending' : (i < currentIdx ? 'done' : (i === currentIdx ? 'active' : 'pending')),
                })),
            };
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

        confirm(message, onYes) {
            this.confirmDialog = { message, onYes, onNo: () => { this.confirmDialog = null; } };
        },
        confirmYes() { if (this.confirmDialog?.onYes) { this.confirmDialog.onYes(); this.confirmDialog = null; } },
        confirmNo() { if (this.confirmDialog?.onNo) this.confirmDialog.onNo(); this.confirmDialog = null; },

        async loadTransport() {
            this.alert = null;
            this.clearValidationErrors();
            const result = await apiRequest(`/api/v1/driver/transports/${this.manifestId}`, { role: 'driver' });
            if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message || 'Failed to load transport manifest.');
                return;
            }
            this.transport = result.payload?.data?.manifest || result.payload?.data?.transport || null;
        },

        async startLoading() {
            if (!this.transport?.id || !this.canStartLoading) return;
            this.actionLoading = true;
            try {
                const result = await apiRequest(`/api/v1/driver/transports/${this.transport.id}/start-loading`, { method: 'POST', role: 'driver' });
                if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
                if (!result.success) { this.setValidationErrors(result.payload); this.showAlert('error', result.message); return; }
                this.showAlert('success', result.message || 'Loading started.');
                await this.loadTransport();
            } catch { this.showAlert('error', 'Unable to start loading right now.'); }
            finally { this.actionLoading = false; }
        },

        async scanItem() {
            const code = (this.scanForm.tracking_code || '').trim();
            if (!code) { this.showAlert('error', 'Please enter a tracking code.'); return; }
            if (!this.transport?.id || !this.canScanItems) return;
            this.scanLoading = true;
            try {
                const result = await apiRequest(`/api/v1/driver/transports/${this.transport.id}/scan-load`, {
                    method: 'POST', role: 'driver', data: { tracking_code: code },
                });
                if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
                if (!result.success) { this.setValidationErrors(result.payload); this.showAlert('error', result.message); return; }
                this.showAlert('success', result.message || `Item ${code} scanned successfully.`);
                this.scanForm.tracking_code = '';
                await this.loadTransport();
            } catch { this.showAlert('error', 'Unable to scan item right now.'); }
            finally { this.scanLoading = false; }
        },

        async depart() {
            if (!this.transport?.id || !this.canDepart) return;
            this.confirm('Confirm departure? Make sure all items are loaded.', async () => {
                this.actionLoading = true;
                try {
                    const result = await apiRequest(`/api/v1/driver/transports/${this.transport.id}/depart`, { method: 'POST', role: 'driver' });
                    if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
                    if (!result.success) { this.setValidationErrors(result.payload); this.showAlert('error', result.message); return; }
                    this.showAlert('success', result.message || 'Departed successfully.');
                    await this.loadTransport();
                } catch { this.showAlert('error', 'Unable to depart right now.'); }
                finally { this.actionLoading = false; }
            });
        },

        async arriveAtDestination() {
            if (!this.transport?.id || !this.canArrive) return;
            this.confirm('Confirm arrival at the destination warehouse?', async () => {
                this.actionLoading = true;
                try {
                    const result = await apiRequest(`/api/v1/driver/transports/${this.transport.id}/arrive`, { method: 'POST', role: 'driver' });
                    if (result.unauthorized) { clearToken('driver'); window.location.href = '/driver/login'; return; }
                    if (!result.success) { this.setValidationErrors(result.payload); this.showAlert('error', result.message); return; }
                    this.showAlert('success', result.message || 'Arrival recorded.');
                    await this.loadTransport();
                } catch { this.showAlert('error', 'Unable to record arrival right now.'); }
                finally { this.actionLoading = false; }
            });
        },
    };
}

function registerDriverTransportComponents() {
    if (!window.Alpine) return;
    window.Alpine.data('driverTransportsListPage', driverTransportsListPage);
    window.Alpine.data('driverTransportShowPage', driverTransportShowPage);
}

if (window.Alpine) {
    registerDriverTransportComponents();
} else {
    document.addEventListener('alpine:init', registerDriverTransportComponents);
}

