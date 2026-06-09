import { createRemoteTablePage } from '../shared/table-page.js';
import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-received-pickups-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-received-pickups-config', null);
    if (!config) {
        console.error('Invalid received pickups config JSON');
    }

    return config;
}

function registerReceivedPickupsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.endpoint) return;

    const pageConfig = {
        endpoint: config.endpoint,
        defaultSort: 'received_at',
        defaultPerPage: 25,
        exportFileName: 'warehouse-received-pickups',
        printTitle: 'Warehouse Received Pickups',
        statuses: config.statuses || [],
        drivers: config.drivers || [],
        columns: [
            { key: 'shipment_number', label: 'Order #', exportLabel: 'Order Number' },
            { key: 'driver_name', label: 'Rider', exportLabel: 'Rider Name' },
            { key: 'status', label: 'Status' },
            { key: 'assigned_at', label: 'Assigned At' },
            { key: 'arrived_warehouse_at', label: 'Arrived Warehouse At' },
            { key: 'received_at', label: 'Received At' },
            { key: 'actions', label: 'Actions', sortable: false },
        ],
    };

    Alpine.data('warehouseReceivedPickupsPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,
            showFilters: false,
            drivers: pageConfig.drivers,
            filters: {
                driver_id: '',
                receipt_result: '',
                notes: '',
                driver_qty_min: '',
                driver_qty_max: '',
            },
            dateFrom: '',
            dateTo: '',
            assignedFrom: '',
            assignedTo: '',
            arrivedFrom: '',
            arrivedTo: '',
            dateRangePicker: null,

            init() {
                this.loadData();
                this.initDateRange();
            },

            buildParams(overrides = {}) {
                const params = page.buildParams.call(this, overrides);

                const dateFrom = overrides.date_from ?? this.dateFrom;
                const dateTo = overrides.date_to ?? this.dateTo;

                if (dateFrom) params.append('date_from', dateFrom);
                if (dateTo) params.append('date_to', dateTo);
                if (this.assignedFrom) params.append('assigned_from', this.assignedFrom);
                if (this.assignedTo) params.append('assigned_to', this.assignedTo);
                if (this.arrivedFrom) params.append('arrived_from', this.arrivedFrom);
                if (this.arrivedTo) params.append('arrived_to', this.arrivedTo);

                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        params.append(key, value);
                    }
                });

                return params;
            },

            initDateRange() {
                if (!this.$refs.dateRange) return;

                const bindPicker = (input, onApply, onClear) => {
                    if (!input) return;
                    if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                    const $input = window.$(input);

                    $input.daterangepicker({
                        autoUpdateInput: false,
                        alwaysShowCalendars: true,
                        opens: 'left',
                        locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
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
                        const from = picker.startDate.format('YYYY-MM-DD');
                        const to = picker.endDate.format('YYYY-MM-DD');
                        onApply(from, to);
                        $input.val(`${from} - ${to}`);
                    });

                    $input.on('cancel.daterangepicker', () => {
                        onClear();
                        $input.val('');
                    });

                    this.dateRangePicker = $input.data('daterangepicker');
                };

                const setupPicker = () => {
                    bindPicker(this.$refs.dateRange, (from, to) => {
                        this.dateFrom = from;
                        this.dateTo = to;
                    }, () => {
                        this.dateFrom = '';
                        this.dateTo = '';
                    });

                    bindPicker(this.$refs.assignedDateRange, (from, to) => {
                        this.assignedFrom = from;
                        this.assignedTo = to;
                    }, () => {
                        this.assignedFrom = '';
                        this.assignedTo = '';
                    });

                    bindPicker(this.$refs.arrivedDateRange, (from, to) => {
                        this.arrivedFrom = from;
                        this.arrivedTo = to;
                    }, () => {
                        this.arrivedFrom = '';
                        this.arrivedTo = '';
                    });
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

            applyFilters() {
                this.meta.current_page = 1;
                this.loadData();
            },

            clearFilters() {
                this.statusFilter = '';
                this.statusFilterName = 'All statuses';
                this.filters = {
                    driver_id: '',
                    receipt_result: '',
                    notes: '',
                    driver_qty_min: '',
                    driver_qty_max: '',
                };
                this.dateFrom = '';
                this.dateTo = '';
                this.assignedFrom = '';
                this.assignedTo = '';
                this.arrivedFrom = '';
                this.arrivedTo = '';

                ['dateRange', 'assignedDateRange', 'arrivedDateRange'].forEach((ref) => {
                    if (!this.$refs[ref]) return;
                    this.$refs[ref].value = '';
                    const picker = window.$?.(this.$refs[ref])?.data?.('daterangepicker');
                    if (picker) {
                        picker.setStartDate(window.moment ? window.moment() : new Date());
                        picker.setEndDate(window.moment ? window.moment() : new Date());
                    }
                });

                this.meta.current_page = 1;
                this.loadData();
            },

            clearFilter(key) {
                if (key === 'status') {
                    this.statusFilter = '';
                    this.statusFilterName = 'All statuses';
                }

                if (key === 'received_date') {
                    this.dateFrom = '';
                    this.dateTo = '';
                    if (this.$refs.dateRange) this.$refs.dateRange.value = '';
                }

                if (key === 'assigned_date') {
                    this.assignedFrom = '';
                    this.assignedTo = '';
                    if (this.$refs.assignedDateRange) this.$refs.assignedDateRange.value = '';
                }

                if (key === 'arrived_date') {
                    this.arrivedFrom = '';
                    this.arrivedTo = '';
                    if (this.$refs.arrivedDateRange) this.$refs.arrivedDateRange.value = '';
                }

                if (key in this.filters) {
                    this.filters[key] = '';
                }

                if (key === 'driver_qty_range') {
                    this.filters.driver_qty_min = '';
                    this.filters.driver_qty_max = '';
                }

                this.meta.current_page = 1;
                this.loadData();
            },

            activeFilterCount() {
                let count = 0;
                if (this.statusFilter) count += 1;
                if (this.dateFrom || this.dateTo) count += 1;
                if (this.assignedFrom || this.assignedTo) count += 1;
                if (this.arrivedFrom || this.arrivedTo) count += 1;
                Object.values(this.filters).forEach((value) => {
                    if (value !== '' && value !== null && value !== undefined) count += 1;
                });
                return count;
            },

            activeFilterChips() {
                const chips = [];

                if (this.statusFilter) {
                    const status = this.statuses.find((item) => item.value === this.statusFilter);
                    chips.push({ key: 'status', label: `Status: ${status?.label || this.statusFilter}` });
                }

                if (this.dateFrom || this.dateTo) {
                    chips.push({ key: 'received_date', label: `Received: ${this.dateFrom || '...'} - ${this.dateTo || '...'}` });
                }

                if (this.assignedFrom || this.assignedTo) {
                    chips.push({ key: 'assigned_date', label: `Assigned: ${this.assignedFrom || '...'} - ${this.assignedTo || '...'}` });
                }

                if (this.arrivedFrom || this.arrivedTo) {
                    chips.push({ key: 'arrived_date', label: `Arrived: ${this.arrivedFrom || '...'} - ${this.arrivedTo || '...'}` });
                }

                if (this.filters.driver_id) {
                    const driver = this.drivers.find((item) => String(item.id) === String(this.filters.driver_id));
                    chips.push({ key: 'driver_id', label: `Rider: ${driver?.name || this.filters.driver_id}` });
                }

                if (this.filters.receipt_result) {
                    const labels = { matched: 'Matched', discrepancy: 'Any discrepancy', shortage: 'Shortage', overage: 'Overage', damaged: 'Damaged' };
                    chips.push({ key: 'receipt_result', label: `Result: ${labels[this.filters.receipt_result] || this.filters.receipt_result}` });
                }

                if (this.filters.notes) {
                    chips.push({ key: 'notes', label: this.filters.notes === 'with_notes' ? 'With notes' : 'Without notes' });
                }

                if (this.filters.driver_qty_min || this.filters.driver_qty_max) {
                    chips.push({ key: 'driver_qty_range', label: `Rider qty: ${this.filters.driver_qty_min || '0'} - ${this.filters.driver_qty_max || '...'}` });
                }

                return chips;
            },

            tableHeaderClass(key) {
                if (key === 'status') return 'text-center';
                if (key === 'actions') return 'text-right';
                return 'text-left';
            },

            tableHeaderContentClass(key) {
                if (key === 'status') return 'justify-center';
                if (key === 'actions') return 'justify-end';
                return '';
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

            statusBadgeClass(status) {
                switch ((status || '').toLowerCase()) {
                    case 'assigned':
                        return 'border-blue-200/60 bg-blue-50 text-blue-700';
                    case 'en_route':
                        return 'border-indigo-200/60 bg-indigo-50 text-indigo-700';
                    case 'arrived':
                        return 'border-amber-200/60 bg-amber-50 text-amber-700';
                    case 'picking_up':
                        return 'border-violet-200/60 bg-violet-50 text-violet-700';
                    case 'completed':
                        return 'border-emerald-200/60 bg-emerald-50 text-emerald-700';
                    case 'cancelled':
                        return 'border-rose-200/60 bg-rose-50 text-rose-700';
                    default:
                        return 'border-slate-200/50 bg-white/60 text-slate-700';
                }
            },
        };
    });
}

if (window.Alpine) {
    registerReceivedPickupsPage();
} else {
    document.addEventListener('alpine:init', registerReceivedPickupsPage);
}
