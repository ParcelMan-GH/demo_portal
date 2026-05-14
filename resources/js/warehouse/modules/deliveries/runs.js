import { createRemoteTablePage } from '../shared/table-page.js';
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

    const statuses = [
        { value: 'draft', label: 'Draft' },
        { value: 'assigned', label: 'Assigned' },
        { value: 'out_for_delivery', label: 'Out For Delivery' },
        { value: 'partially_delivered', label: 'Partially Delivered' },
        { value: 'completed', label: 'Completed' },
        { value: 'cancelled', label: 'Cancelled' },
    ];
    const stopStatuses = [
        { value: 'pending', label: 'Pending' },
        { value: 'arrived', label: 'Arrived' },
        { value: 'delivered', label: 'Delivered' },
        { value: 'failed', label: 'Failed' },
        { value: 'handed_off', label: 'Handed Off' },
    ];

    const pageConfig = {
        endpoint: config.data_endpoint,
        defaultSort: 'created_at',
        exportFileName: 'warehouse-delivery-runs',
        printTitle: 'Warehouse Delivery Runs',
        statuses,
        columns: [
            { key: 'run_number', label: 'Run #', exportLabel: 'Run Number' },
            { key: 'status', label: 'Status' },
            { key: 'driver_name', label: 'Driver', exportLabel: 'Driver Name' },
            { key: 'stops_count', label: 'Stops', sortable: false },
            { key: 'items_count', label: 'Items', sortable: false },
            { key: 'assigned_at', label: 'Assigned At' },
            { key: 'dispatched_at', label: 'Dispatched At' },
            { key: 'completed_at', label: 'Completed At' },
            { key: 'actions', label: 'Actions', sortable: false },
        ],
    };

    Alpine.data('warehouseDeliveryRunsPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,
            showFilters: false,
            deliveryDrivers: Array.isArray(config.delivery_drivers) ? config.delivery_drivers : [],
            localDeliveryBatches: Array.isArray(config.local_delivery_batches) ? config.local_delivery_batches : [],
            runStats: config.run_stats || {},
            stopStatuses,
            filters: {
                created_date_from: '',
                created_date_to: '',
                assigned_date_from: '',
                assigned_date_to: '',
                dispatched_date_from: '',
                dispatched_date_to: '',
                completed_date_from: '',
                completed_date_to: '',
                status: '',
                driver_id: '',
                stop_status: '',
                verification: '',
                stops_min: '',
                stops_max: '',
                items_min: '',
                items_max: '',
            },
            selectedDriverByRun: {},
            newRunBatchId: '',
            canResetCodes: Boolean(config.can_reset_codes),
            showCreateModal: false,
            createMode: 'batch',
            eligibleItems: [],
            eligibleSearch: '',
            eligibleLoading: false,
            selectedReceiptItemIds: [],
            expandedRunId: null,

            init() {
                this.loadData();
                this.initDateRange();
            },

            buildParams(overrides = {}) {
                const params = page.buildParams.call(this, overrides);
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        params.set(key, value);
                    }
                });
                return params;
            },

            applyFilters() {
                this.meta.current_page = 1;
                this.loadData();
            },

            clearFilters() {
                Object.keys(this.filters).forEach((key) => { this.filters[key] = ''; });
                ['createdDateRange', 'assignedDateRange', 'dispatchedDateRange', 'completedDateRange'].forEach((ref) => {
                    if (this.$refs[ref]) this.$refs[ref].value = '';
                });
                this.meta.current_page = 1;
                this.loadData();
            },

            clearFilter(key) {
                if (!Object.prototype.hasOwnProperty.call(this.filters, key)) return;
                this.filters[key] = '';

                const dateRefs = {
                    created_date_from: 'createdDateRange',
                    created_date_to: 'createdDateRange',
                    assigned_date_from: 'assignedDateRange',
                    assigned_date_to: 'assignedDateRange',
                    dispatched_date_from: 'dispatchedDateRange',
                    dispatched_date_to: 'dispatchedDateRange',
                    completed_date_from: 'completedDateRange',
                    completed_date_to: 'completedDateRange',
                };
                if (dateRefs[key] && this.$refs[dateRefs[key]]) {
                    const fromKey = key.replace('_to', '_from');
                    const toKey = key.replace('_from', '_to');
                    this.filters[fromKey] = '';
                    this.filters[toKey] = '';
                    this.$refs[dateRefs[key]].value = '';
                }

                this.applyFilters();
            },

            activeFilterChips() {
                const labels = {
                    created_date_from: 'Created date',
                    assigned_date_from: 'Assigned date',
                    dispatched_date_from: 'Dispatched date',
                    completed_date_from: 'Completed date',
                    status: 'Run status',
                    driver_id: 'Driver',
                    stop_status: 'Stop status',
                    verification: 'Verification',
                    stops_min: 'Min stops',
                    stops_max: 'Max stops',
                    items_min: 'Min items',
                    items_max: 'Max items',
                };

                return Object.entries(this.filters)
                    .filter(([key, value]) => value !== '' && value !== null && value !== undefined && !key.endsWith('_to'))
                    .map(([key, value]) => ({ key, label: `${labels[key] || key}: ${this.filterValueLabel(key, value)}` }));
            },

            filterValueLabel(key, value) {
                if (key.endsWith('_from')) {
                    const toKey = key.replace('_from', '_to');
                    return [value, this.filters[toKey]].filter(Boolean).join(' - ');
                }

                if (key === 'status') {
                    return this.statusLabel(value);
                }

                if (key === 'driver_id') {
                    return this.deliveryDrivers.find((driver) => String(driver.id) === String(value))?.name || value;
                }

                if (key === 'stop_status') {
                    return this.stopStatuses.find((status) => status.value === value)?.label || value;
                }

                if (key === 'verification') {
                    return {
                        verified: 'Verified by code',
                        skipped: 'Verification skipped',
                        code_sent: 'Code sent',
                        no_code: 'No code sent',
                    }[value] || value;
                }

                return value;
            },

            isSortable(key) {
                const column = this.columns.find((item) => item.key === key);
                return Boolean(column && column.sortable !== false);
            },

            isSortedColumn(key) {
                const column = this.columns.find((item) => item.key === key);
                return this.sortBy === (column?.sortKey || key);
            },

            sort(column) {
                if (!this.isSortable(column)) return;

                const sortKey = this.columns.find((item) => item.key === column)?.sortKey || column;
                if (this.sortBy === sortKey) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortBy = sortKey;
                    this.sortDirection = 'asc';
                }

                this.meta.current_page = 1;
                this.loadData();
            },

            tableHeaderClass(key) {
                if (['status', 'stops_count', 'items_count'].includes(key)) return 'text-center';
                if (key === 'actions') return 'text-right';
                return 'text-left';
            },

            tableHeaderContentClass(key) {
                if (['status', 'stops_count', 'items_count'].includes(key)) return 'justify-center';
                if (key === 'actions') return 'justify-end';
                return '';
            },

            statusBadgeClass(status) {
                switch ((status || '').toLowerCase()) {
                    case 'draft':
                        return 'border-slate-200/60 bg-slate-50 text-slate-700';
                    case 'assigned':
                        return 'border-blue-200/60 bg-blue-50 text-blue-700';
                    case 'out_for_delivery':
                        return 'border-violet-200/60 bg-violet-50 text-violet-700';
                    case 'partially_delivered':
                        return 'border-amber-200/60 bg-amber-50 text-amber-700';
                    case 'completed':
                        return 'border-emerald-200/60 bg-emerald-50 text-emerald-700';
                    case 'cancelled':
                        return 'border-rose-200/60 bg-rose-50 text-rose-700';
                    default:
                        return 'border-slate-200/50 bg-white/60 text-slate-700';
                }
            },

            statusLabel(status) {
                const normalized = String(status || '').toLowerCase();
                return statuses.find(item => item.value === normalized)?.label || (status || '-');
            },

            formatDisplayDate(value) {
                if (!value) return '-';

                const normalized = String(value).replace(' ', 'T');
                const date = new Date(normalized);
                if (Number.isNaN(date.getTime())) return value;

                return date.toLocaleString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                });
            },

            initDateRange() {
                const setupPicker = (refName, fromKey, toKey) => {
                    if (!this.$refs[refName]) return;
                    if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;
                    const $input = window.$(this.$refs[refName]);
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
                        this.filters[fromKey] = picker.startDate.format('YYYY-MM-DD');
                        this.filters[toKey] = picker.endDate.format('YYYY-MM-DD');
                        $input.val(`${this.filters[fromKey]} - ${this.filters[toKey]}`);
                    });
                    $input.on('cancel.daterangepicker', () => {
                        this.filters[fromKey] = '';
                        this.filters[toKey] = '';
                        $input.val('');
                    });
                };

                const setupPickers = () => {
                    setupPicker('createdDateRange', 'created_date_from', 'created_date_to');
                    setupPicker('assignedDateRange', 'assigned_date_from', 'assigned_date_to');
                    setupPicker('dispatchedDateRange', 'dispatched_date_from', 'dispatched_date_to');
                    setupPicker('completedDateRange', 'completed_date_from', 'completed_date_to');
                };

                if (window.$ && window.moment && window.$.fn.daterangepicker) {
                    setupPickers();
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
                    .then(() => loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js'))
                    .then(setupPickers);
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

            async createRun() {
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
                        body: JSON.stringify(this.newRunBatchId ? { sort_batch_id: Number(this.newRunBatchId) } : {}),
                    });

                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to create delivery run.');
                    }

                    if (result.data?.redirect_url) {
                        window.location.href = result.data.redirect_url;
                        return;
                    }

                    window.showToast?.(result.message || 'Draft delivery run created.', 'success');
                    this.newRunBatchId = '';
                    this.showCreateModal = false;
                    await this.loadData();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to create delivery run.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            async loadEligibleItems() {
                this.eligibleLoading = true;
                try {
                    const params = new URLSearchParams();
                    params.set('per_page', '200');
                    if (this.eligibleSearch) params.set('search', this.eligibleSearch);

                    const response = await fetch(`${config.eligible_items_endpoint}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Failed to load eligible items.');

                    this.eligibleItems = Array.isArray(result.data) ? result.data : [];
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to load eligible items.', 'error');
                } finally {
                    this.eligibleLoading = false;
                }
            },

            toggleAllEligible(event) {
                if (event.target.checked) {
                    this.selectedReceiptItemIds = this.eligibleItems.map(item => item.warehouse_receipt_item_id);
                } else {
                    this.selectedReceiptItemIds = [];
                }
            },

            toggleItem(id) {
                const index = this.selectedReceiptItemIds.indexOf(id);
                if (index === -1) {
                    this.selectedReceiptItemIds.push(id);
                } else {
                    this.selectedReceiptItemIds.splice(index, 1);
                }
            },

            async createRunFromItems() {
                if (this.selectedReceiptItemIds.length === 0) {
                    window.showToast?.('Select at least one item.', 'warning');
                    return;
                }

                this.loading = true;
                try {
                    const response = await fetch(config.create_from_items_endpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            warehouse_receipt_item_ids: this.selectedReceiptItemIds.map(id => Number(id)),
                        }),
                    });

                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to create delivery run.');
                    }

                    window.showToast?.(result.message || 'Delivery run created successfully.', 'success');
                    this.selectedReceiptItemIds = [];
                    this.showCreateModal = false;
                    this.eligibleItems = [];
                    this.eligibleSearch = '';
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
        };
    });
}

if (window.Alpine) {
    registerWarehouseDeliveryRunsPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseDeliveryRunsPage);
}
