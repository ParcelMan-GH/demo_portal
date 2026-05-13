import { createRemoteTablePage } from '../shared/table-page.js';
import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-pending-receipts-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-pending-receipts-config', null);
    if (!config) {
        console.error('Invalid incoming packages config JSON');
    }

    return config;
}

function registerPendingReceiptsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.endpoint) return;

    const pageConfig = {
        endpoint: config.endpoint,
        defaultSort: 'assigned_at',
        exportFileName: 'warehouse-incoming-packages',
        printTitle: 'Warehouse Incoming Packages',
        statuses: (config.statuses || []).filter((status) => status.value !== 'assigned'),
        columns: [
            { key: 'shipment_number', label: 'Shipment #', exportLabel: 'Shipment Number' },
            { key: 'driver_name', label: 'Driver', exportLabel: 'Driver Name' },
            { key: 'driver_phone', label: 'Driver Phone' },
            { key: 'status', label: 'Status' },
            { key: 'assigned_at', label: 'Assigned At' },
            { key: 'arrived_warehouse_at', label: 'Arrived Warehouse At' },
            { key: 'actions', label: 'Actions', sortable: false },
        ],
    };

    Alpine.data('warehousePendingReceiptsPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,
            dateFrom: '',
            dateTo: '',
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

                return params;
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
                            'Today': [window.moment(), window.moment()],
                            'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                            'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                            'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                            'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                            'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                        },
                    });

                    $input.on('apply.daterangepicker', (ev, picker) => {
                        this.dateFrom = picker.startDate.format('YYYY-MM-DD');
                        this.dateTo = picker.endDate.format('YYYY-MM-DD');
                        $input.val(`${this.dateFrom} - ${this.dateTo}`);
                        this.meta.current_page = 1;
                        this.loadData();
                    });

                    $input.on('cancel.daterangepicker', () => {
                        this.dateFrom = '';
                        this.dateTo = '';
                        $input.val('');
                        this.meta.current_page = 1;
                        this.loadData();
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
        };
    });
}

if (window.Alpine) {
    registerPendingReceiptsPage();
} else {
    document.addEventListener('alpine:init', registerPendingReceiptsPage);
}
