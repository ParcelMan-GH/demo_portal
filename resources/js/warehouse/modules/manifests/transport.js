import { createRemoteTablePage } from '../shared/table-page.js';
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

function registerWarehouseTransportManifestsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.data_endpoint) return;

    const statuses = [
        { value: 'ready', label: 'Ready' },
        { value: 'assigned', label: 'Assigned' },
        { value: 'loading', label: 'Loading' },
        { value: 'on_the_road', label: 'On the Road' },
        { value: 'arrived', label: 'Arrived' },
        { value: 'completed', label: 'Completed' },
        { value: 'needs_driver', label: 'Needs Driver' },
        { value: 'cancelled', label: 'Cancelled' },
    ];

    const pageConfig = {
        endpoint: config.data_endpoint,
        defaultSort: 'created_at',
        defaultPerPage: 25,
        exportFileName: 'outgoing-transfers',
        printTitle: 'Outgoing Transfers',
        statuses,
        columns: [
            { key: 'manifest_number', label: 'Manifest', exportLabel: 'Manifest Number' },
            { key: 'batch_number', label: 'Batch', exportLabel: 'Batch', sortKey: 'sort_batch_id', visible: false },
            { key: 'destination_warehouse', label: 'Destination', exportLabel: 'Destination', sortKey: 'destination_warehouse_id' },
            { key: 'status', label: 'Status' },
            { key: 'driver_name', label: 'Driver', exportLabel: 'Driver', sortable: false },
            { key: 'items_count', label: 'Items', exportLabel: 'Items' },
            { key: 'loaded_count', label: 'Loaded', exportLabel: 'Loaded', sortable: false },
            { key: 'created_at', label: 'Created At', exportLabel: 'Created At' },
            { key: 'dispatched_at', label: 'Dispatched At', exportLabel: 'Dispatched At', visible: false },
            { key: 'arrived_at', label: 'Arrived At', exportLabel: 'Arrived At', visible: false },
            { key: 'completed_at', label: 'Completed At', exportLabel: 'Completed At', sortKey: 'received_at', visible: false },
            { key: 'actions', label: 'Actions', sortable: false },
        ],
    };

    Alpine.data('warehouseTransportManifestsPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,
            showFilters: false,
            transferBatches: Array.isArray(config.transfer_batches) ? config.transfer_batches : [],
            transportDrivers: Array.isArray(config.transport_drivers) ? config.transport_drivers : [],
            destinationWarehouses: Array.isArray(config.destination_warehouses) ? config.destination_warehouses : [],
            summary: { total: 0, ready: 0, assigned: 0, loading: 0, on_the_road: 0, arrived: 0, completed: 0, needs_driver: 0 },
            statCards: [
                { key: 'total', label: 'Total', icon: 'truck', iconClass: 'bg-slate-100 text-slate-700 ring-slate-200' },
                { key: 'ready', label: 'Ready', icon: 'box', iconClass: 'bg-orange-50 text-orange-700 ring-orange-200' },
                { key: 'assigned', label: 'Assigned', icon: 'driver', iconClass: 'bg-blue-50 text-blue-700 ring-blue-200' },
                { key: 'loading', label: 'Loading', icon: 'box', iconClass: 'bg-indigo-50 text-indigo-700 ring-indigo-200' },
                { key: 'on_the_road', label: 'On the Road', icon: 'road', iconClass: 'bg-violet-50 text-violet-700 ring-violet-200' },
                { key: 'arrived', label: 'Arrived', icon: 'pin', iconClass: 'bg-amber-50 text-amber-700 ring-amber-200' },
                { key: 'completed', label: 'Completed', icon: 'check', iconClass: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
                { key: 'needs_driver', label: 'Needs Driver', icon: 'driver', iconClass: 'bg-rose-50 text-rose-700 ring-rose-200' },
            ],
            filters: {
                status: '',
                destination_warehouse_id: '',
                driver_id: '',
                assigned_state: '',
                date_type: 'created_at',
                date_from: '',
                date_to: '',
            },
            dateRangePicker: null,

            init() {
                this.loadData();
                this.initDateRange();
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

            buildParams(overrides = {}) {
                const params = page.buildParams.call(this, overrides);
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        params.set(key, value);
                    }
                });
                params.delete('statusFilter');
                return params;
            },

            async loadData() {
                this.loading = true;
                try {
                    const response = await fetch(`${this.endpoint}?${this.buildParams().toString()}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Failed to fetch outgoing transfers.');
                    this.rows = Array.isArray(result.data) ? result.data : [];
                    this.meta = result.meta || this.meta;
                    this.summary = result.summary || this.summary;
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to load outgoing transfers.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            applyFilters() {
                this.meta.current_page = 1;
                this.loadData();
            },

            resetFiltersOnly() {
                Object.keys(this.filters).forEach((key) => {
                    this.filters[key] = key === 'date_type' ? 'created_at' : '';
                });
                this.search = '';
                if (this.$refs.dateRange) this.$refs.dateRange.value = '';
            },

            clearFilters() {
                this.resetFiltersOnly();
                this.meta.current_page = 1;
                this.loadData();
            },

            applySummaryFilter(key) {
                this.resetFiltersOnly();
                this.meta.current_page = 1;

                if (key !== 'total') {
                    this.filters.status = key;
                }

                this.loadData();
            },

            clearFilter(key) {
                if (!Object.prototype.hasOwnProperty.call(this.filters, key)) return;

                this.filters[key] = key === 'date_type' ? 'created_at' : '';
                if ((key === 'date_from' || key === 'date_to') && this.$refs.dateRange) {
                    this.$refs.dateRange.value = '';
                    this.filters.date_from = '';
                    this.filters.date_to = '';
                }
                this.applyFilters();
            },

            activeFilterChips() {
                const labels = {
                    status: 'Status',
                    destination_warehouse_id: 'Destination',
                    driver_id: 'Driver',
                    assigned_state: 'Assignment',
                    date_from: 'Date from',
                    date_to: 'Date to',
                };

                return Object.entries(this.filters)
                    .filter(([key, value]) => key !== 'date_type' && value !== '' && value !== null && value !== undefined)
                    .map(([key, value]) => ({ key, label: `${labels[key] || key}: ${this.filterValueLabel(key, value)}` }));
            },

            filterValueLabel(key, value) {
                if (key === 'status') return this.statuses.find((item) => item.value === value)?.label || value;
                if (key === 'destination_warehouse_id') return this.destinationWarehouses.find((item) => String(item.id) === String(value))?.name || value;
                if (key === 'driver_id') return this.transportDrivers.find((item) => String(item.id) === String(value))?.name || value;
                if (key === 'assigned_state') return value === 'unassigned' ? 'Needs driver' : 'Assigned';
                return String(value).replace(/_/g, ' ');
            },

            tableHeaderClass(key) {
                if (['status', 'items_count', 'loaded_count'].includes(key)) return 'text-center';
                if (key === 'actions') return 'text-right';
                return 'text-left';
            },

            tableHeaderContentClass(key) {
                if (['status', 'items_count', 'loaded_count'].includes(key)) return 'justify-center';
                if (key === 'actions') return 'justify-end';
                return '';
            },

            statusLabel(status) {
                return this.statuses.find((item) => item.value === status)?.label || String(status || '').replace(/_/g, ' ');
            },

            statusBadgeClass(status) {
                switch ((status || '').toLowerCase()) {
                    case 'draft':
                    case 'ready':
                        return 'border-orange-200 bg-orange-50 text-orange-700';
                    case 'assigned':
                        return 'border-blue-200 bg-blue-50 text-blue-700';
                    case 'loading':
                        return 'border-indigo-200 bg-indigo-50 text-indigo-700';
                    case 'in_transit':
                    case 'on_the_road':
                        return 'border-violet-200 bg-violet-50 text-violet-700';
                    case 'arrived':
                        return 'border-amber-200 bg-amber-50 text-amber-700';
                    case 'received':
                    case 'completed':
                        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                    case 'cancelled':
                        return 'border-rose-200 bg-rose-50 text-rose-700';
                    default:
                        return 'border-slate-200 bg-slate-50 text-slate-700';
                }
            },

            formatDisplayDate(value) {
                if (!value) return '-';

                const normalized = String(value).replace(' ', 'T');
                const date = new Date(normalized);
                if (Number.isNaN(date.getTime())) return value;

                return new Intl.DateTimeFormat('en-GH', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true,
                }).format(date);
            },

            initDateRange() {
                if (!this.$refs.dateRange) return;

                const setupPicker = () => {
                    if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                    const $input = window.$(this.$refs.dateRange);

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
                        this.filters.date_from = picker.startDate.format('YYYY-MM-DD');
                        this.filters.date_to = picker.endDate.format('YYYY-MM-DD');
                        $input.val(`${this.filters.date_from} - ${this.filters.date_to}`);
                        this.applyFilters();
                    });

                    $input.on('cancel.daterangepicker', () => {
                        this.filters.date_from = '';
                        this.filters.date_to = '';
                        $input.val('');
                        this.applyFilters();
                    });

                    this.dateRangePicker = $input.data('daterangepicker');
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
            },

            async createManifest() {
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
                        body: JSON.stringify({}),
                    });

                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to create outgoing transfer.');
                    }

                    window.showToast?.(result.message || 'Outgoing transfer created successfully.', 'success');
                    if (result.data?.redirect_url) {
                        window.location.href = result.data.redirect_url;
                        return;
                    }
                    await this.loadData();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to create outgoing transfer.', 'error');
                } finally {
                    this.loading = false;
                }
            },
        };
    });
}

if (window.Alpine) {
    registerWarehouseTransportManifestsPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseTransportManifestsPage);
}
