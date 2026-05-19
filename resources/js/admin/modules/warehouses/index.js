import './view.js';

/**
 * Warehouses page Alpine component
 */

function buildWarehousesTable(config) {
    return {
        endpoint: config.endpoint,
        exportEndpoint: config.exportEndpoint,
        storeEndpoint: config.storeEndpoint,
        regionsEndpoint: config.regionsEndpoint || '',
        districtsEndpointTemplate: config.districtsEndpointTemplate || '',
        showEndpointTemplate: config.showEndpointTemplate || '',
        csrfToken: config.csrfToken,
        warehouses: [],
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },
        loading: false,
        search: '',
        showFilters: false,
        statusFilter: '',
        regionFilter: '',
        districtFilter: '',
        filterDistricts: [],
        capacityMin: '',
        capacityMax: '',
        usersMin: '',
        usersMax: '',
        createdFrom: '',
        createdTo: '',
        dateRangePicker: null,
        perPage: 50,
        sortBy: 'created_at',
        sortDirection: 'desc',
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'code', label: 'Code' },
            { key: 'region', label: 'Region' },
            { key: 'district', label: 'District' },
            { key: 'contact_phone', label: 'Contact Phone' },
            { key: 'users_count', label: 'Users' },
            { key: 'capacity', label: 'Capacity (m³)' },
            { key: 'is_active', label: 'Status' },
            { key: 'created_at', label: 'Created At' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            name: true,
            code: true,
            region: true,
            district: true,
            contact_phone: true,
            users_count: true,
            capacity: true,
            is_active: true,
            created_at: true,
            actions: true,
        },

        // Modal state
        showModal: false,
        modalMode: 'add', // 'add', 'edit', 'view'
        editingWarehouseId: null,
        saving: false,
        errors: {},
        form: {
            name: '',
            code: '',
            address: '',
            region_id: '',
            district_id: '',
            contact_phone: '',
            contact_email: '',
            capacity: '',
            is_active: true,
        },

        // Region/District state
        regions: [],
        districts: [],
        loadingDistricts: false,

        init() {
            this.loadRegions();
            this.initDateRange();
            this.loadData();
        },

        async loadRegions() {
            if (!this.regionsEndpoint) return;

            try {
                const response = await fetch(this.regionsEndpoint, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch regions');

                const result = await response.json();
                this.regions = Array.isArray(result.data) ? result.data : (Array.isArray(result) ? result : []);
            } catch (error) {
                console.error('Error loading regions:', error);
            }
        },

        async onRegionChange() {
            this.form.district_id = '';
            this.districts = [];

            const regionId = this.form.region_id;
            if (!regionId) return;

            this.loadingDistricts = true;
            try {
                const endpoint = this.districtsEndpointTemplate
                    ? this.districtsEndpointTemplate.replace('__REGION__', regionId)
                    : '';

                if (!endpoint) {
                    throw new Error('Districts endpoint is not configured');
                }

                const response = await fetch(endpoint, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch districts');

                const result = await response.json();
                this.districts = Array.isArray(result.data) ? result.data : (Array.isArray(result) ? result : []);
            } catch (error) {
                console.error('Error loading districts:', error);
            } finally {
                this.loadingDistricts = false;
            }
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.meta.current_page,
                    per_page: this.perPage,
                    sort: this.sortBy,
                    direction: this.sortDirection,
                });

                if (this.search) params.append('search', this.search);
                if (this.statusFilter) params.append('status', this.statusFilter);
                if (this.regionFilter) params.append('region_id', this.regionFilter);
                if (this.districtFilter) params.append('district_id', this.districtFilter);
                if (this.capacityMin) params.append('capacity_min', this.capacityMin);
                if (this.capacityMax) params.append('capacity_max', this.capacityMax);
                if (this.usersMin) params.append('users_min', this.usersMin);
                if (this.usersMax) params.append('users_max', this.usersMax);
                if (this.createdFrom) params.append('date_from', this.createdFrom);
                if (this.createdTo) params.append('date_to', this.createdTo);

                const response = await fetch(`${this.endpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch data');

                const result = await response.json();
                this.warehouses = result.data;
                this.meta = {
                    current_page: result.meta.current_page,
                    from: result.meta.from,
                    to: result.meta.to,
                    total: result.meta.total,
                    last_page: result.meta.last_page,
                };
            } catch (error) {
                console.error('Error loading warehouses:', error);
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

        setStatusFilter(value) {
            this.statusFilter = value;
            this.meta.current_page = 1;
            this.loadData();
        },

        clearStatusFilter() {
            this.setStatusFilter('');
        },

        statusFilterLabel() {
            if (this.statusFilter === 'active') return 'Active';
            if (this.statusFilter === 'inactive') return 'Inactive';
            return 'All statuses';
        },

        activeCount() {
            return this.meta.active_count ?? this.warehouses.filter((warehouse) => warehouse.is_active).length;
        },

        inactiveCount() {
            return this.meta.inactive_count ?? this.warehouses.filter((warehouse) => !warehouse.is_active).length;
        },

        staffCount() {
            return this.meta.users_count ?? this.warehouses.reduce((sum, warehouse) => sum + Number(warehouse.users_count || 0), 0);
        },

        async onFilterRegionChange() {
            this.districtFilter = '';
            this.filterDistricts = [];

            if (!this.regionFilter) return;

            try {
                const endpoint = this.districtsEndpointTemplate
                    ? this.districtsEndpointTemplate.replace('__REGION__', this.regionFilter)
                    : '';

                if (!endpoint) return;

                const response = await fetch(endpoint, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch districts');

                const result = await response.json();
                this.filterDistricts = Array.isArray(result.data) ? result.data : (Array.isArray(result) ? result : []);
            } catch (error) {
                console.error('Error loading filter districts:', error);
            }
        },

        applyFilters() {
            this.meta.current_page = 1;
            this.loadData();
        },

        clearFilters() {
            this.statusFilter = '';
            this.regionFilter = '';
            this.districtFilter = '';
            this.filterDistricts = [];
            this.capacityMin = '';
            this.capacityMax = '';
            this.usersMin = '';
            this.usersMax = '';
            this.clearDateFilter(false);
            this.applyFilters();
        },

        clearFilter(key) {
            if (key === 'status') this.statusFilter = '';
            if (key === 'region') {
                this.regionFilter = '';
                this.districtFilter = '';
                this.filterDistricts = [];
            }
            if (key === 'district') this.districtFilter = '';
            if (key === 'capacity') {
                this.capacityMin = '';
                this.capacityMax = '';
            }
            if (key === 'users') {
                this.usersMin = '';
                this.usersMax = '';
            }
            if (key === 'created') this.clearDateFilter(false);
            this.applyFilters();
        },

        activeFilterChips() {
            const chips = [];
            if (this.statusFilter) chips.push({ key: 'status', label: this.statusFilterLabel() });
            if (this.regionFilter) chips.push({ key: 'region', label: (this.regions.find((region) => String(region.id) === String(this.regionFilter)) || { name: 'Region' }).name });
            if (this.districtFilter) chips.push({ key: 'district', label: (this.filterDistricts.find((district) => String(district.id) === String(this.districtFilter)) || { name: 'District' }).name });
            if (this.capacityMin || this.capacityMax) chips.push({ key: 'capacity', label: `Capacity ${this.capacityMin || 0}-${this.capacityMax || 'any'} m³` });
            if (this.usersMin || this.usersMax) chips.push({ key: 'users', label: `Users ${this.usersMin || 0}-${this.usersMax || 'any'}` });
            if (this.createdFrom || this.createdTo) chips.push({ key: 'created', label: `${this.createdFrom || 'Start'} to ${this.createdTo || 'Today'}` });
            return chips;
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        initDateRange() {
            if (!this.$refs.createdRange) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                const $input = window.$(this.$refs.createdRange);

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
                    this.createdFrom = picker.startDate.format('YYYY-MM-DD');
                    this.createdTo = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.createdFrom} - ${this.createdTo}`);
                    this.loadData();
                });

                $input.on('cancel.daterangepicker', () => {
                    this.clearDateFilter();
                });

                this.dateRangePicker = $input.data('daterangepicker');
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

        clearDateFilter(reload = true) {
            this.createdFrom = '';
            this.createdTo = '';
            if (this.dateRangePicker) {
                this.dateRangePicker.setStartDate(window.moment());
                this.dateRangePicker.setEndDate(window.moment());
            }
            if (this.$refs.createdRange) {
                this.$refs.createdRange.value = '';
            }
            if (reload) this.loadData();
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

        // Modal methods
        openAddModal() {
            this.modalMode = 'add';
            this.editingWarehouseId = null;
            this.errors = {};
            this.districts = [];
            this.form = {
                name: '',
                code: '',
                address: '',
                region_id: '',
                district_id: '',
                contact_phone: '',
                contact_email: '',
                capacity: '',
                is_active: true,
            };
            this.showModal = true;
        },

        async openEditModal(warehouse) {
            this.modalMode = 'edit';
            this.editingWarehouseId = warehouse.id;
            this.errors = {};

            let warehouseData = warehouse;

            // Fetch full details if table row does not include location IDs.
            if ((warehouse.region_id === undefined || warehouse.district_id === undefined) && this.showEndpointTemplate) {
                try {
                    const endpoint = this.showEndpointTemplate.replace('__ID__', warehouse.id);
                    const response = await fetch(endpoint, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.ok) {
                        const result = await response.json();
                        if (result.warehouse) {
                            warehouseData = { ...warehouse, ...result.warehouse };
                        }
                    }
                } catch (error) {
                    console.error('Error loading warehouse details:', error);
                }
            }

            this.form = {
                name: warehouseData.name,
                code: warehouseData.code || '',
                address: warehouseData.address || '',
                region_id: warehouseData.region_id || '',
                district_id: warehouseData.district_id || '',
                contact_phone: warehouseData.contact_phone || '',
                contact_email: warehouseData.contact_email || '',
                capacity: warehouseData.capacity || '',
                is_active: warehouseData.is_active,
            };

            // Load districts if a region is set
            if (this.form.region_id) {
                this.loadingDistricts = true;
                try {
                    const endpoint = this.districtsEndpointTemplate
                        ? this.districtsEndpointTemplate.replace('__REGION__', this.form.region_id)
                        : '';

                    if (!endpoint) {
                        throw new Error('Districts endpoint is not configured');
                    }

                    const response = await fetch(endpoint, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.ok) {
                        const result = await response.json();
                        this.districts = Array.isArray(result.data) ? result.data : (Array.isArray(result) ? result : []);
                    }
                } catch (error) {
                    console.error('Error loading districts:', error);
                } finally {
                    this.loadingDistricts = false;
                }
            } else {
                this.districts = [];
            }

            this.showModal = true;
        },

        viewWarehouse(warehouse) {
            this.modalMode = 'view';
            this.editingWarehouseId = warehouse.id;
            this.errors = {};
            this.form = {
                name: warehouse.name,
                code: warehouse.code || '',
                address: warehouse.address || '',
                region_id: warehouse.region_id || '',
                district_id: warehouse.district_id || '',
                contact_phone: warehouse.contact_phone || '',
                contact_email: warehouse.contact_email || '',
                capacity: warehouse.capacity || '',
                is_active: warehouse.is_active,
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.errors = {};
        },

        async saveWarehouse() {
            this.saving = true;
            this.errors = {};

            try {
                const url = this.modalMode === 'add'
                    ? this.storeEndpoint
                    : `${window.location.origin}/admin/warehouses/${this.editingWarehouseId}`;

                const method = this.modalMode === 'add' ? 'POST' : 'PUT';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.form),
                });

                const result = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        this.errors = result.errors || {};
                    } else {
                        throw new Error(result.message || 'Failed to save warehouse');
                    }
                    return;
                }

                this.closeModal();
                this.loadData();
            } catch (error) {
                console.error('Error saving warehouse:', error);
            } finally {
                this.saving = false;
            }
        },

        async toggleWarehouseStatus(warehouse) {
            try {
                const response = await fetch(`${window.location.origin}/admin/warehouses/${warehouse.id}/toggle-active`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok) {
                    this.loadData();
                }
            } catch (err) {
                console.error('Failed to update warehouse status:', err);
            }
        },

        openDeleteModal(warehouse) {
            if (this.$store?.warehousesDelete) {
                this.$store.warehousesDelete.warehouse = warehouse;
                this.$store.warehousesDelete.onConfirm = () => this.confirmDelete();
                this.$store.warehousesDelete.show = true;
            }
        },

        async confirmDelete() {
            const store = this.$store?.warehousesDelete;
            if (!store?.warehouse) return;

            store.deleting = true;

            try {
                const response = await fetch(`${window.location.origin}/admin/warehouses/${store.warehouse.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to delete warehouse');
                }

                store.show = false;
                store.warehouse = null;
                this.loadData();
            } catch (error) {
                console.error('Error deleting warehouse:', error);
                alert('Failed to delete warehouse. Please try again.');
            } finally {
                store.deleting = false;
            }
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.statusFilter) params.append('status', this.statusFilter);
                if (this.regionFilter) params.append('region_id', this.regionFilter);
                if (this.districtFilter) params.append('district_id', this.districtFilter);
                if (this.capacityMin) params.append('capacity_min', this.capacityMin);
                if (this.capacityMax) params.append('capacity_max', this.capacityMax);
                if (this.usersMin) params.append('users_min', this.usersMin);
                if (this.usersMax) params.append('users_max', this.usersMax);
                if (this.createdFrom) params.append('date_from', this.createdFrom);
                if (this.createdTo) params.append('date_to', this.createdTo);
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
                if (this.search) params.append('search', this.search);
                if (this.statusFilter) params.append('status', this.statusFilter);
                if (this.regionFilter) params.append('region_id', this.regionFilter);
                if (this.districtFilter) params.append('district_id', this.districtFilter);
                if (this.capacityMin) params.append('capacity_min', this.capacityMin);
                if (this.capacityMax) params.append('capacity_max', this.capacityMax);
                if (this.usersMin) params.append('users_min', this.usersMin);
                if (this.usersMax) params.append('users_max', this.usersMax);
                if (this.createdFrom) params.append('date_from', this.createdFrom);
                if (this.createdTo) params.append('date_to', this.createdTo);

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

            doc.title = 'Warehouses Export';
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
            title.textContent = 'Warehouses List';
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

            this.downloadFile(csvContent, 'warehouses.csv', 'text/csv');
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
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    };
}

function getWarehousesConfig() {
    const container = document.querySelector('[data-warehouses-config]');
    let config = window.warehousesTableConfig || null;

    if (container) {
        try {
            config = JSON.parse(container.getAttribute('data-warehouses-config'));
        } catch (error) {
            console.error('Invalid warehouses config JSON:', error);
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

function registerWarehousesTable() {
    if (!window.Alpine) {
        return;
    }

    const config = getWarehousesConfig();
    if (!config) {
        return;
    }

    if (!window.warehousesTable) {
        window.warehousesTable = () => buildWarehousesTable(config);
    }

    Alpine.store('warehousesDelete', {
        show: false,
        warehouse: null,
        deleting: false,
        onConfirm: null,
    });

    Alpine.data('warehousesTable', window.warehousesTable);
}

if (window.Alpine) {
    registerWarehousesTable();
} else {
    document.addEventListener('alpine:init', registerWarehousesTable);
}
