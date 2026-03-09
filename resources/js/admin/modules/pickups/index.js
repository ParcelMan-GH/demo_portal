/**
 * Pickup Assignments page Alpine component
 */

function buildPickupsTable(config) {
    return {
        endpoint: config.endpoint,
        availableDriversEndpoint: config.availableDriversEndpoint,
        availableWarehousesEndpoint: config.availableWarehousesEndpoint,
        updateEndpointTemplate: config.updateEndpointTemplate,
        cancelEndpointTemplate: config.cancelEndpointTemplate,
        receiveEndpointTemplate: config.receiveEndpointTemplate,
        csrfToken: '',

        assignments: [],
        meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },

        loading: false,
        search: '',
        statusFilter: '',
        statusFilterName: 'All statuses',
        assignedFrom: '',
        assignedTo: '',
        dateRangePicker: null,
        perPage: 50,
        sortBy: 'assigned_at',
        sortDirection: 'desc',
        page: 1,

        columns: [
            { key: 'shipment', label: 'Shipment' },
            { key: 'vendor', label: 'Vendor' },
            { key: 'driver', label: 'Driver' },
            { key: 'warehouse', label: 'Target Warehouse' },
            { key: 'status', label: 'Status' },
            { key: 'assigned_at', label: 'Assigned At' },
            { key: 'completed_at', label: 'Completed At' },
            { key: 'assigned_by', label: 'Assigned By' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            shipment: true,
            vendor: true,
            driver: true,
            warehouse: true,
            status: true,
            assigned_at: true,
            completed_at: true,
            assigned_by: true,
            actions: true,
        },

        // Edit modal
        showEditModal: false,
        editSaving: false,
        editTarget: null,
        editForm: { driver_id: '', target_warehouse_id: '' },
        availableDrivers: [],
        availableWarehouses: [],

        // Cancel modal
        showCancelModal: false,
        cancelSaving: false,
        cancelTarget: null,
        cancelReason: '',

        // Receive modal
        showReceiveModal: false,
        receiveSaving: false,
        receiveTarget: null,
        receiveForm: { received_warehouse_id: '', receive_notes: '' },

        init() {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            this.csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            this.initDateRange();
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    per_page: this.perPage,
                    search: this.search,
                    status: this.statusFilter,
                    sort: this.sortBy,
                    direction: this.sortDirection,
                    date_from: this.assignedFrom,
                    date_to: this.assignedTo,
                });

                const response = await fetch(`${this.endpoint}?${params}`);
                const data = await response.json();

                this.assignments = data.data;
                this.meta = data.meta;
            } catch (error) {
                console.error('Failed to load pickup assignments:', error);
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
            this.page = 1;
            this.loadData();
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        // Pagination
        firstPage() { this.page = 1; this.loadData(); },
        previousPage() { if (this.page > 1) { this.page--; this.loadData(); } },
        nextPage() { if (this.page < this.meta.last_page) { this.page++; this.loadData(); } },
        lastPage() { this.page = this.meta.last_page; this.loadData(); },

        // Date range picker
        initDateRange() {
            const input = this.$refs.assignedRange;
            if (!input) return;

            const loadDateRangePicker = () => {
                if (window.$ && window.$.fn && window.$.fn.daterangepicker) {
                    this.setupDateRangePicker(input);
                    return;
                }
                const loadScript = (id, src) => new Promise(resolve => {
                    if (document.getElementById(id)) return resolve();
                    const s = document.createElement('script');
                    s.id = id; s.src = src; s.onload = resolve;
                    document.body.appendChild(s);
                });
                const cssId = 'daterangepicker-css';
                if (!document.getElementById(cssId)) {
                    const link = document.createElement('link');
                    link.id = cssId; link.rel = 'stylesheet';
                    link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                    document.head.appendChild(link);
                }
                loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                    .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                    .then(() => {
                        window.$ = window.jQuery = window.jQuery || window.$;
                        window.moment = window.moment || moment;
                        return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
                    })
                    .then(() => this.setupDateRangePicker(input));
            };

            if (document.readyState === 'complete') {
                loadDateRangePicker();
            } else {
                window.addEventListener('load', loadDateRangePicker);
            }
        },

        setupDateRangePicker(input) {
            if (!window.$ || !window.$.fn || !window.$.fn.daterangepicker) return;

            window.$(input).daterangepicker({
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                opens: 'left',
                locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                ranges: {
                    'Today':       [window.moment(), window.moment()],
                    'Yesterday':   [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                    'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                    'Last 30 Days':[window.moment().subtract(29, 'days'), window.moment()],
                    'This Month':  [window.moment().startOf('month'), window.moment().endOf('month')],
                    'Last Month':  [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                },
            });

            window.$(input).on('apply.daterangepicker', (ev, picker) => {
                this.assignedFrom = picker.startDate.format('YYYY-MM-DD');
                this.assignedTo = picker.endDate.format('YYYY-MM-DD');
                input.value = this.assignedFrom + ' - ' + this.assignedTo;
                this.page = 1;
                this.loadData();
            });

            window.$(input).on('cancel.daterangepicker', () => {
                this.assignedFrom = '';
                this.assignedTo = '';
                input.value = '';
                this.page = 1;
                this.loadData();
            });
        },

        // Status badge styling
        statusBadgeClass(status) {
            const map = {
                assigned: 'bg-blue-100 text-blue-700',
                en_route: 'bg-amber-100 text-amber-700',
                arrived: 'bg-indigo-100 text-indigo-700',
                picking_up: 'bg-violet-100 text-violet-700',
                completed: 'bg-emerald-100 text-emerald-700',
                cancelled: 'bg-rose-100 text-rose-700',
            };
            return map[status] || 'bg-slate-100 text-slate-700';
        },

        // Edit modal
        async openEditModal(assignment) {
            this.editTarget = assignment;
            this.editForm.driver_id = assignment.driver_id;
            this.editForm.target_warehouse_id = assignment.target_warehouse_id;
            this.showEditModal = true;
            await this.loadDropdownData();
        },

        async loadDropdownData() {
            if (this.availableDrivers.length && this.availableWarehouses.length) return;
            try {
                const [driversRes, warehousesRes] = await Promise.all([
                    fetch(this.availableDriversEndpoint),
                    fetch(this.availableWarehousesEndpoint),
                ]);
                const driversData = await driversRes.json();
                const warehousesData = await warehousesRes.json();
                this.availableDrivers = driversData.data || [];
                this.availableWarehouses = warehousesData.data || [];
            } catch (error) {
                console.error('Failed to load dropdown data:', error);
            }
        },

        async saveEdit() {
            this.editSaving = true;
            try {
                const url = this.updateEndpointTemplate.replace('__ID__', this.editTarget.id);
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(this.editForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.showEditModal = false;
                    this.loadData();
                    if (window.showToast) window.showToast('Assignment updated successfully', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to update', 'error');
                }
            } catch (error) {
                console.error('Save edit error:', error);
                if (window.showToast) window.showToast('Failed to update assignment', 'error');
            } finally {
                this.editSaving = false;
            }
        },

        // Cancel modal
        openCancelModal(assignment) {
            this.cancelTarget = assignment;
            this.cancelReason = '';
            this.showCancelModal = true;
        },

        async confirmCancel() {
            this.cancelSaving = true;
            try {
                const url = this.cancelEndpointTemplate.replace('__ID__', this.cancelTarget.id);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({ cancellation_reason: this.cancelReason }),
                });
                const data = await response.json();
                if (data.success) {
                    this.showCancelModal = false;
                    this.loadData();
                    if (window.showToast) window.showToast('Assignment cancelled', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to cancel', 'error');
                }
            } catch (error) {
                console.error('Cancel error:', error);
                if (window.showToast) window.showToast('Failed to cancel assignment', 'error');
            } finally {
                this.cancelSaving = false;
            }
        },

        // Receive modal
        async openReceiveModal(assignment) {
            this.receiveTarget = assignment;
            this.receiveForm = { received_warehouse_id: '', receive_notes: '' };
            this.showReceiveModal = true;
            await this.loadDropdownData();
        },

        async confirmReceive() {
            this.receiveSaving = true;
            try {
                const url = this.receiveEndpointTemplate.replace('__ID__', this.receiveTarget.id);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(this.receiveForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.showReceiveModal = false;
                    this.loadData();
                    if (window.showToast) window.showToast('Pickup received at warehouse', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to receive', 'error');
                }
            } catch (error) {
                console.error('Receive error:', error);
                if (window.showToast) window.showToast('Failed to receive pickup', 'error');
            } finally {
                this.receiveSaving = false;
            }
        },

        // Export
        downloadCSV() {
            if (!this.assignments.length) return;
            const headers = ['Shipment #', 'Vendor', 'Driver', 'Phone', 'Target Warehouse', 'Status', 'Assigned At', 'Completed At', 'Assigned By'];
            const rows = this.assignments.map(a => [
                a.shipment_number, a.vendor_name, a.driver_name, a.driver_phone,
                a.target_warehouse, a.status_label, a.assigned_at || '', a.completed_at || '', a.assigned_by,
            ]);

            const csvContent = [
                headers.join(','),
                ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')),
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `pickup_assignments_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        printData() {
            if (!this.assignments.length) return;
            const printWindow = window.open('', '_blank');
            if (!printWindow) return;

            const doc = printWindow.document;
            doc.title = 'Pickup Assignments';
            doc.body.innerHTML = '';

            const style = doc.createElement('style');
            style.textContent = [
                'body { font-family: -apple-system, sans-serif; padding: 20px; }',
                'h1 { font-size: 24px; margin-bottom: 20px; color: #1e293b; }',
                'table { width: 100%; border-collapse: collapse; margin-top: 20px; }',
                'th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; font-size: 12px; }',
                'th { background-color: #f1f5f9; font-weight: 600; color: #475569; }',
                'tr:nth-child(even) { background-color: #f8fafc; }',
            ].join('\n');
            doc.head.appendChild(style);

            const title = doc.createElement('h1');
            title.textContent = 'Pickup Assignments';
            doc.body.appendChild(title);

            const headers = ['Shipment #', 'Vendor', 'Driver', 'Target Warehouse', 'Status', 'Assigned At', 'Completed At'];
            const table = doc.createElement('table');
            const thead = doc.createElement('thead');
            const headRow = doc.createElement('tr');
            headers.forEach(h => { const th = doc.createElement('th'); th.textContent = h; headRow.appendChild(th); });
            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = doc.createElement('tbody');
            this.assignments.forEach(a => {
                const tr = doc.createElement('tr');
                [a.shipment_number, a.vendor_name, a.driver_name, a.target_warehouse, a.status_label, a.assigned_at || '-', a.completed_at || '-'].forEach(val => {
                    const td = doc.createElement('td');
                    td.textContent = val;
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            doc.body.appendChild(table);

            setTimeout(() => printWindow.print(), 250);
        },

        formatDateTime(value) {
            if (!value) return '-';
            const date = new Date(value);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    };
}

function getPickupsConfig() {
    const container = document.querySelector('[data-pickups-config]');
    if (!container) return null;

    try {
        return JSON.parse(container.getAttribute('data-pickups-config'));
    } catch (error) {
        console.error('Invalid pickups config JSON:', error);
        return null;
    }
}

function registerPickupsTable() {
    if (!window.Alpine) return;

    const config = getPickupsConfig();
    if (!config) return;

    Alpine.data('pickupsTable', () => buildPickupsTable(config));
}

if (window.Alpine) {
    registerPickupsTable();
} else {
    document.addEventListener('alpine:init', registerPickupsTable);
}
