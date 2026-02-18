import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-delivery-runs-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-delivery-runs-config', null);
    if (!config) {
        console.error('Invalid warehouse delivery runs config JSON');
    }

    return config;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function withRun(urlTemplate, runId) {
    return (urlTemplate || '').replace('__RUN__', String(runId));
}

function withRunAndStop(urlTemplate, runId, stopId) {
    return withRun(urlTemplate, runId).replace('__STOP__', String(stopId));
}

function registerWarehouseDeliveryRunsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.data_endpoint) return;

    window.Alpine.data('warehouseDeliveryRunsPage', () => ({
        loading: false,
        rows: [],
        search: '',
        statusFilter: '',
        perPage: 10,
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },
        deliveryDrivers: Array.isArray(config.delivery_drivers) ? config.delivery_drivers : [],
        localDeliveryBatches: Array.isArray(config.local_delivery_batches) ? config.local_delivery_batches : [],
        selectedDriverByRun: {},
        newRunBatchId: '',
        canResetCodes: Boolean(config.can_reset_codes),

        async init() {
            await this.loadData();
        },

        statusClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'draft':
                    return 'border-slate-200 bg-slate-50 text-slate-700';
                case 'assigned':
                    return 'border-blue-200 bg-blue-50 text-blue-700';
                case 'out_for_delivery':
                    return 'border-violet-200 bg-violet-50 text-violet-700';
                case 'partially_delivered':
                    return 'border-amber-200 bg-amber-50 text-amber-700';
                case 'completed':
                    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                case 'cancelled':
                    return 'border-rose-200 bg-rose-50 text-rose-700';
                default:
                    return 'border-slate-200 bg-slate-50 text-slate-700';
            }
        },

        stopStatusClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'delivered':
                    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                case 'arrived':
                    return 'border-indigo-200 bg-indigo-50 text-indigo-700';
                case 'failed':
                    return 'border-rose-200 bg-rose-50 text-rose-700';
                default:
                    return 'border-slate-200 bg-slate-50 text-slate-700';
            }
        },

        canDispatch(row) {
            return (row?.status || '').toLowerCase() === 'assigned' && Boolean(row?.driver_name) && !this.loading;
        },

        canResendCode(row, stop) {
            if (!this.canResetCodes || this.loading) return false;
            const runStatus = (row?.status || '').toLowerCase();
            if (!['assigned', 'out_for_delivery', 'partially_delivered'].includes(runStatus)) return false;
            return ['pending', 'arrived', 'failed'].includes((stop?.status || '').toLowerCase());
        },

        buildParams() {
            const params = new URLSearchParams();
            params.set('page', String(this.meta.current_page || 1));
            params.set('per_page', String(this.perPage || 10));
            if (this.search) params.set('search', this.search);
            if (this.statusFilter) params.set('status', this.statusFilter);
            return params;
        },

        async loadData() {
            this.loading = true;
            try {
                const response = await fetch(`${config.data_endpoint}?${this.buildParams().toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Failed to load delivery runs.');
                }

                this.rows = Array.isArray(result.data) ? result.data : [];
                this.meta = result.meta || this.meta;
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to load delivery runs.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async createRun() {
            if (!this.newRunBatchId) {
                window.showToast?.('Select a sealed local delivery batch first.', 'warning');
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(config.create_endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        sort_batch_id: Number(this.newRunBatchId),
                    }),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to create delivery run.');
                }

                window.showToast?.(result.message || 'Delivery run created successfully.', 'success');
                this.newRunBatchId = '';
                await this.loadData();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to create delivery run.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async assignDriver(runId) {
            const driverId = this.selectedDriverByRun[runId];
            if (!driverId) {
                window.showToast?.('Select a delivery driver first.', 'warning');
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(withRun(config.assign_endpoint, runId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ driver_id: Number(driverId) }),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to assign driver.');
                }

                window.showToast?.(result.message || 'Driver assigned successfully.', 'success');
                delete this.selectedDriverByRun[runId];
                await this.loadData();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to assign delivery driver.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async dispatchRun(runId) {
            this.loading = true;
            try {
                const response = await fetch(withRun(config.dispatch_endpoint, runId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to dispatch delivery run.');
                }

                window.showToast?.(result.message || 'Delivery run dispatched successfully.', 'success');
                await this.loadData();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to dispatch delivery run.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async resendCode(runId, stopId) {
            this.loading = true;
            try {
                const response = await fetch(withRunAndStop(config.resend_code_endpoint, runId, stopId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to resend verification code.');
                }

                window.showToast?.(result.message || 'Verification code resent successfully.', 'success');
                await this.loadData();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to resend verification code.', 'error');
            } finally {
                this.loading = false;
            }
        },

        setStatusFilter(value) {
            this.statusFilter = value || '';
            this.meta.current_page = 1;
            this.loadData();
        },

        setPerPage(value) {
            this.perPage = Number(value) || 10;
            this.meta.current_page = 1;
            this.loadData();
        },

        firstPage() {
            if ((this.meta.current_page || 1) > 1) {
                this.meta.current_page = 1;
                this.loadData();
            }
        },

        previousPage() {
            if ((this.meta.current_page || 1) > 1) {
                this.meta.current_page -= 1;
                this.loadData();
            }
        },

        nextPage() {
            if ((this.meta.current_page || 1) < (this.meta.last_page || 1)) {
                this.meta.current_page += 1;
                this.loadData();
            }
        },

        lastPage() {
            const lastPage = this.meta.last_page || 1;
            if ((this.meta.current_page || 1) < lastPage) {
                this.meta.current_page = lastPage;
                this.loadData();
            }
        },
    }));
}

if (window.Alpine) {
    registerWarehouseDeliveryRunsPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseDeliveryRunsPage);
}

