import './view.js';

/**
 * Orders page Alpine component
 */

function buildOrdersTable(config) {
    return {
        endpoint: config.endpoint,
        exportEndpoint: config.exportEndpoint,
        csrfToken: config.csrfToken,
        statuses: config.statuses || [],
        pickupStatuses: config.pickupStatuses || [],
        sources: config.sources || [],
        destinationModes: config.destinationModes || [],
        fulfillmentTypes: config.fulfillmentTypes || [],
        deliveryPreferences: config.deliveryPreferences || [],
        regions: config.regions || [],
        warehouses: config.warehouses || [],
        drivers: config.drivers || [],
        shipments: [],
        summary: {
            total: 0,
            needs_driver: 0,
            assigned_pickup: 0,
            picked_up: 0,
            received_warehouse: 0,
            discrepancies: 0,
        },
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },
        loading: false,
        loadError: '',
        filtersOpen: false,
        search: '',
        statusFilter: '',
        pickupStatusFilter: '',
        sourceFilter: '',
        destinationModeFilter: '',
        deliveryPreferenceFilter: '',
        fulfillmentTypeFilter: '',
        assignmentStateFilter: '',
        warehouseFilter: '',
        driverFilter: '',
        pickupRegionFilter: '',
        deliveryRegionFilter: '',
        quantityStateFilter: '',
        summaryStateFilter: '',
        itemsMin: '',
        itemsMax: '',
        vendorQtyMin: '',
        vendorQtyMax: '',
        driverQtyMin: '',
        driverQtyMax: '',
        createdFrom: '',
        createdTo: '',
        submittedFrom: '',
        submittedTo: '',
        assignedFrom: '',
        assignedTo: '',
        pickedFrom: '',
        pickedTo: '',
        receivedFrom: '',
        receivedTo: '',
        dateRangePickers: {},
        perPage: 50,
        sortBy: 'created_at',
        sortDirection: 'desc',
        columns: [
            { key: 'shipment_number', label: 'Order #' },
            { key: 'vendor', label: 'Vendor' },
            { key: 'pickup_contact', label: 'Pickup Contact' },
            { key: 'pickup_location', label: 'Pickup Location' },
            { key: 'target_warehouse', label: 'Drop-off Warehouse' },
            { key: 'pickup_driver', label: 'Pickup Driver' },
            { key: 'items', label: 'Packages' },
            { key: 'status', label: 'Order Status' },
            { key: 'pickup_status', label: 'Pickup Status' },
            { key: 'submitted_at', label: 'Submitted At' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            shipment_number: true,
            vendor: true,
            pickup_contact: true,
            pickup_location: true,
            target_warehouse: true,
            pickup_driver: true,
            items: true,
            status: true,
            pickup_status: true,
            submitted_at: true,
            actions: true,
        },
        statCards: [
            { key: 'total', label: 'Total Orders', icon: 'package', iconClass: 'bg-slate-50 text-slate-700 ring-slate-200' },
            { key: 'needs_driver', label: 'Needs Driver', icon: 'user', iconClass: 'bg-amber-50 text-amber-700 ring-amber-100' },
            { key: 'assigned_pickup', label: 'Assigned / Pickup', icon: 'truck', iconClass: 'bg-blue-50 text-blue-700 ring-blue-100' },
            { key: 'picked_up', label: 'Picked Up', icon: 'check', iconClass: 'bg-indigo-50 text-indigo-700 ring-indigo-100' },
            { key: 'received_warehouse', label: 'Received at Warehouse', icon: 'warehouse', iconClass: 'bg-emerald-50 text-emerald-700 ring-emerald-100' },
            { key: 'discrepancies', label: 'Discrepancies', icon: 'alert', iconClass: 'bg-rose-50 text-rose-700 ring-rose-100' },
        ],

        init() {
            this.initDateRange();
            if (this.endpoint) {
                this.loadData();
            }
        },

        async loadData() {
            this.loading = true;
            this.loadError = '';
            try {
                const params = new URLSearchParams({
                    page: this.meta.current_page,
                    per_page: this.perPage,
                    sort: this.sortBy,
                    direction: this.sortDirection,
                });

                this.appendFilters(params);

                const response = await fetch(`${this.endpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    let message = 'Failed to fetch orders.';
                    try {
                        const error = await response.json();
                        message = error.message || message;
                    } catch (_) {
                        message = response.status === 403 ? 'You are not allowed to load orders.' : message;
                    }
                    throw new Error(message);
                }

                const result = await response.json();
                this.shipments = result.data;
                this.summary = {
                    ...this.summary,
                    ...(result.summary || {}),
                };
                this.meta = {
                    current_page: result.meta.current_page,
                    from: result.meta.from,
                    to: result.meta.to,
                    total: result.meta.total,
                    last_page: result.meta.last_page,
                };
            } catch (error) {
                console.error('Error loading orders:', error);
                this.shipments = [];
                this.loadError = error.message || 'Failed to load orders.';
            } finally {
                this.loading = false;
            }
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDirection = 'asc';
            }
            this.loadData();
        },

        resetFilters() {
            this.search = '';
            this.statusFilter = '';
            this.pickupStatusFilter = '';
            this.sourceFilter = '';
            this.destinationModeFilter = '';
            this.deliveryPreferenceFilter = '';
            this.fulfillmentTypeFilter = '';
            this.assignmentStateFilter = '';
            this.warehouseFilter = '';
            this.driverFilter = '';
            this.pickupRegionFilter = '';
            this.deliveryRegionFilter = '';
            this.quantityStateFilter = '';
            this.summaryStateFilter = '';
            this.itemsMin = '';
            this.itemsMax = '';
            this.vendorQtyMin = '';
            this.vendorQtyMax = '';
            this.driverQtyMin = '';
            this.driverQtyMax = '';
            this.createdFrom = '';
            this.createdTo = '';
            this.submittedFrom = '';
            this.submittedTo = '';
            this.assignedFrom = '';
            this.assignedTo = '';
            this.pickedFrom = '';
            this.pickedTo = '';
            this.receivedFrom = '';
            this.receivedTo = '';
            ['created', 'submitted', 'assigned', 'picked', 'received'].forEach((key) => this.clearDateFilter(key));
            this.meta.current_page = 1;
            this.loadData();
        },

        activeFilterCount() {
            return [
                this.statusFilter,
                this.pickupStatusFilter,
                this.sourceFilter,
                this.destinationModeFilter,
                this.deliveryPreferenceFilter,
                this.fulfillmentTypeFilter,
                this.assignmentStateFilter,
                this.warehouseFilter,
                this.driverFilter,
                this.pickupRegionFilter,
                this.deliveryRegionFilter,
                this.quantityStateFilter,
                this.summaryStateFilter,
                this.itemsMin,
                this.itemsMax,
                this.vendorQtyMin,
                this.vendorQtyMax,
                this.driverQtyMin,
                this.driverQtyMax,
                this.createdFrom || this.createdTo,
                this.submittedFrom || this.submittedTo,
                this.assignedFrom || this.assignedTo,
                this.pickedFrom || this.pickedTo,
                this.receivedFrom || this.receivedTo,
            ].filter(Boolean).length;
        },

        appendFilters(params) {
            const filters = {
                search: this.search,
                status: this.statusFilter,
                pickup_status: this.pickupStatusFilter,
                source: this.sourceFilter,
                destination_mode: this.destinationModeFilter,
                delivery_preference: this.deliveryPreferenceFilter,
                fulfillment_type: this.fulfillmentTypeFilter,
                assignment_state: this.assignmentStateFilter,
                target_warehouse_id: this.warehouseFilter,
                driver_id: this.driverFilter,
                pickup_region_id: this.pickupRegionFilter,
                delivery_region_id: this.deliveryRegionFilter,
                quantity_state: this.quantityStateFilter,
                summary_state: this.summaryStateFilter,
                items_min: this.itemsMin,
                items_max: this.itemsMax,
                vendor_qty_min: this.vendorQtyMin,
                vendor_qty_max: this.vendorQtyMax,
                driver_qty_min: this.driverQtyMin,
                driver_qty_max: this.driverQtyMax,
                created_from: this.createdFrom,
                created_to: this.createdTo,
                submitted_from: this.submittedFrom,
                submitted_to: this.submittedTo,
                assigned_from: this.assignedFrom,
                assigned_to: this.assignedTo,
                picked_from: this.pickedFrom,
                picked_to: this.pickedTo,
                received_from: this.receivedFrom,
                received_to: this.receivedTo,
            };

            Object.entries(filters).forEach(([key, value]) => {
                if (value !== '' && value !== null && value !== undefined) {
                    params.append(key, value);
                }
            });
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        applySummaryFilter(key) {
            this.summaryStateFilter = key === 'total' || this.summaryStateFilter === key ? '' : key;
            this.meta.current_page = 1;
            this.loadData();
        },

        isSummaryActive(key) {
            return key !== 'total' && this.summaryStateFilter === key;
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        initDateRange() {
            const ranges = [
                { key: 'created', ref: 'createdRange', from: 'createdFrom', to: 'createdTo' },
                { key: 'submitted', ref: 'submittedRange', from: 'submittedFrom', to: 'submittedTo' },
                { key: 'assigned', ref: 'assignedRange', from: 'assignedFrom', to: 'assignedTo' },
                { key: 'picked', ref: 'pickedRange', from: 'pickedFrom', to: 'pickedTo' },
                { key: 'received', ref: 'receivedRange', from: 'receivedFrom', to: 'receivedTo' },
            ];

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                ranges.forEach((range) => {
                    if (!this.$refs[range.ref]) return;

                    const $input = window.$(this.$refs[range.ref]);

                    $input.daterangepicker({
                        autoUpdateInput: false,
                        alwaysShowCalendars: true,
                        opens: 'right',
                        locale: {
                            format: 'YYYY-MM-DD',
                            cancelLabel: 'Clear',
                        },
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
                        this[range.from] = picker.startDate.format('YYYY-MM-DD');
                        this[range.to] = picker.endDate.format('YYYY-MM-DD');
                        $input.val(`${this[range.from]} - ${this[range.to]}`);
                    });

                    $input.on('cancel.daterangepicker', () => {
                        this.clearDateFilter(range.key);
                    });

                    this.dateRangePickers[range.key] = $input.data('daterangepicker');
                });
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            // Load from CDN
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

        clearDateFilter(key = 'created') {
            const ranges = {
                created: { ref: 'createdRange', from: 'createdFrom', to: 'createdTo' },
                submitted: { ref: 'submittedRange', from: 'submittedFrom', to: 'submittedTo' },
                assigned: { ref: 'assignedRange', from: 'assignedFrom', to: 'assignedTo' },
                picked: { ref: 'pickedRange', from: 'pickedFrom', to: 'pickedTo' },
                received: { ref: 'receivedRange', from: 'receivedFrom', to: 'receivedTo' },
            };
            const range = ranges[key];
            if (!range) return;

            this[range.from] = '';
            this[range.to] = '';
            if (this.dateRangePickers[key]) {
                this.dateRangePickers[key].setStartDate(window.moment());
                this.dateRangePickers[key].setEndDate(window.moment());
            }
            if (this.$refs[range.ref]) {
                this.$refs[range.ref].value = '';
            }
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) {
                this.meta.current_page++;
                this.loadData();
            }
        },

        firstPage() {
            if (this.meta.current_page !== 1) {
                this.meta.current_page = 1;
                this.loadData();
            }
        },

        lastPage() {
            if (this.meta.current_page !== this.meta.last_page) {
                this.meta.current_page = this.meta.last_page;
                this.loadData();
            }
        },

        previousPage() {
            if (this.meta.current_page > 1) {
                this.meta.current_page--;
                this.loadData();
            }
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                this.appendFilters(params);
                params.append('format', format);

                if (format === 'excel' || format === 'pdf') {
                    window.location.href = `${this.exportEndpoint}?${params}`;
                    return;
                }

                const response = await fetch(`${this.exportEndpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Export failed');

                const result = await response.json();

                if (format === 'csv') {
                    this.downloadCSV(result.data);
                }
            } catch (err) {
                console.error('Export failed:', err);
                alert('Export failed. Please try again.');
            }
        },

        async printData() {
            try {
                const params = new URLSearchParams();
                this.appendFilters(params);

                const response = await fetch(`${this.exportEndpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch data');

                const result = await response.json();
                this.openPrintWindow(result.data);
            } catch (err) {
                console.error('Print failed:', err);
                alert('Print failed. Please try again.');
            }
        },

        openPrintWindow(data) {
            if (!data.length) {
                alert('No data to print');
                return;
            }

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Pop-up blocked. Please allow pop-ups to print.');
                return;
            }
            const doc = printWindow.document;
            const headers = Object.keys(data[0]);

            doc.title = 'Orders Export';
            doc.body.innerHTML = '';

            const style = doc.createElement('style');
            style.textContent = [
                'body { font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; padding: 20px; }',
                'h1 { font-size: 24px; margin-bottom: 20px; color: #1e293b; }',
                'table { width: 100%; border-collapse: collapse; margin-top: 20px; }',
                'th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; font-size: 12px; }',
                'th { background-color: #f1f5f9; font-weight: 600; color: #475569; }',
                'tr:nth-child(even) { background-color: #f8fafc; }',
            ].join('\n');
            doc.head.appendChild(style);

            const title = doc.createElement('h1');
            title.textContent = 'Orders List';
            doc.body.appendChild(title);

            const meta = doc.createElement('p');
            meta.style.color = '#64748b';
            meta.style.fontSize = '14px';
            meta.style.marginBottom = '20px';
            meta.textContent = `Generated on ${new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            })}`;
            doc.body.appendChild(meta);

            const table = doc.createElement('table');
            const thead = doc.createElement('thead');
            const headRow = doc.createElement('tr');
            headers.forEach((header) => {
                const th = doc.createElement('th');
                th.textContent = header;
                headRow.appendChild(th);
            });
            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = doc.createElement('tbody');
            data.forEach((row) => {
                const tr = doc.createElement('tr');
                headers.forEach((header) => {
                    const td = doc.createElement('td');
                    const value = row[header];
                    td.textContent = value === null || value === undefined || value === '' ? '-' : String(value);
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            doc.body.appendChild(table);

            setTimeout(() => printWindow.print(), 250);
        },

        downloadCSV(data) {
            if (!data.length) return;

            const headers = Object.keys(data[0]);
            const csvContent = [
                headers.join(','),
                ...data.map(row =>
                    headers.map(header => {
                        let cell = row[header] ?? '';
                        cell = String(cell).replace(/"/g, '""');
                        return `"${cell}"`;
                    }).join(',')
                )
            ].join('\n');

            this.downloadFile(csvContent, 'orders.csv', 'text/csv');
        },

        downloadFile(content, filename, type) {
            const blob = new Blob([content], { type });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        formatDateTime(value) {
            if (!value) return '-';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString('en-GH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });
        },

        statusBadgeClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'draft':
                    return 'border-slate-200 bg-slate-50 text-slate-700';
                case 'submitted':
                case 'invoice_sent':
                case 'invoice_accepted':
                    return 'border-blue-200 bg-blue-50 text-blue-700';
                case 'pickup_assigned':
                case 'picked_up':
                case 'arrived_warehouse':
                case 'at_warehouse':
                case 'sorted':
                    return 'border-violet-200 bg-violet-50 text-violet-700';
                case 'in_transit':
                case 'at_destination':
                case 'out_for_delivery':
                    return 'border-amber-200 bg-amber-50 text-amber-700';
                case 'delivered':
                    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                case 'cancelled':
                    return 'border-rose-200 bg-rose-50 text-rose-700';
                default:
                    return 'border-slate-200 bg-white text-slate-700';
            }
        },

        pickupBadgeClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'assigned':
                    return 'border-blue-200 bg-blue-50 text-blue-700';
                case 'en_route':
                    return 'border-indigo-200 bg-indigo-50 text-indigo-700';
                case 'arrived':
                    return 'border-amber-200 bg-amber-50 text-amber-700';
                case 'picking_up':
                    return 'border-violet-200 bg-violet-50 text-violet-700';
                case 'completed':
                    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                case 'cancelled':
                    return 'border-rose-200 bg-rose-50 text-rose-700';
                default:
                    return 'border-orange-200 bg-orange-50 text-orange-700';
            }
        },
    };
}

function getOrdersConfig() {
    const container = document.querySelector('[data-shipments-config]');
    let config = window.shipmentsTableConfig || null;

    if (container) {
        try {
            config = JSON.parse(container.getAttribute('data-shipments-config'));
        } catch (error) {
            console.error('Invalid shipments config JSON:', error);
        }
    }

    if (!config) {
        return null;
    }

    if (!config.csrfToken) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        config.csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    }

    return config;
}

function registerOrdersTable() {
    if (!window.Alpine) {
        return;
    }

    Alpine.data('shipmentsTable', window.shipmentsTable);
}

window.shipmentsTable = () => {
    const config = getOrdersConfig();

    if (!config) {
        return buildOrdersTable({
            endpoint: '',
            exportEndpoint: '',
            statuses: [],
            pickupStatuses: [],
            warehouses: [],
            drivers: [],
        });
    }

    return buildOrdersTable(config);
};

if (window.Alpine) {
    registerOrdersTable();
} else {
    document.addEventListener('alpine:init', registerOrdersTable);
}
