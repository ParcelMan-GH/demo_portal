import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-transport-manifests-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-transport-manifests-config', null);
    if (!config) {
        console.error('Invalid warehouse transport manifests config JSON');
    }

    return config;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function withManifest(urlTemplate, manifestId) {
    return (urlTemplate || '').replace('__MANIFEST__', String(manifestId));
}

function registerWarehouseTransportManifestsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.data_endpoint) return;

    window.Alpine.data('warehouseTransportManifestsPage', () => ({
        loading: false,
        rows: [],
        search: '',
        statusFilter: '',
        perPage: 10,
        newManifestBatchId: '',
        transportDrivers: Array.isArray(config.transport_drivers) ? config.transport_drivers : [],
        transferBatches: Array.isArray(config.transfer_batches) ? config.transfer_batches : [],
        selectedDriverByManifest: {},
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },

        async init() {
            await this.loadData();
        },

        statusClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'draft':
                    return 'border-slate-200 bg-slate-50 text-slate-700';
                case 'assigned':
                    return 'border-blue-200 bg-blue-50 text-blue-700';
                case 'loading':
                    return 'border-indigo-200 bg-indigo-50 text-indigo-700';
                case 'in_transit':
                    return 'border-violet-200 bg-violet-50 text-violet-700';
                case 'arrived':
                    return 'border-amber-200 bg-amber-50 text-amber-700';
                case 'received':
                    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                case 'cancelled':
                    return 'border-rose-200 bg-rose-50 text-rose-700';
                default:
                    return 'border-slate-200 bg-slate-50 text-slate-700';
            }
        },

        canDispatch(row) {
            return ['assigned'].includes((row?.status || '').toLowerCase()) && Boolean(row?.driver_name) && !this.loading;
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
                    throw new Error(result.message || 'Failed to load transport manifests.');
                }

                this.rows = Array.isArray(result.data) ? result.data : [];
                this.meta = result.meta || this.meta;
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to load transport manifests.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async createManifest() {
            if (!this.newManifestBatchId) {
                window.showToast?.('Select a transfer batch first.', 'warning');
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
                        sort_batch_id: Number(this.newManifestBatchId),
                    }),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to create manifest.');
                }

                window.showToast?.(result.message || 'Manifest created successfully.', 'success');
                this.newManifestBatchId = '';
                await this.loadData();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to create manifest.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async assignDriver(manifestId) {
            const driverId = this.selectedDriverByManifest[manifestId];
            if (!driverId) {
                window.showToast?.('Select a driver first.', 'warning');
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(withManifest(config.assign_endpoint, manifestId), {
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
                delete this.selectedDriverByManifest[manifestId];
                await this.loadData();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to assign driver.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async dispatchManifest(manifestId) {
            this.loading = true;
            try {
                const response = await fetch(withManifest(config.dispatch_endpoint, manifestId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to dispatch manifest.');
                }

                window.showToast?.(result.message || 'Manifest dispatched successfully.', 'success');
                await this.loadData();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to dispatch manifest.', 'error');
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
    registerWarehouseTransportManifestsPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseTransportManifestsPage);
}

