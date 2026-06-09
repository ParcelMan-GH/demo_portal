import './view.js';

/**
 * Drivers page Alpine component
 */

function buildDriversTable(config) {
    return {
        endpoint: config.endpoint,
        exportEndpoint: config.exportEndpoint,
        storeEndpoint: config.storeEndpoint,
        baseEndpoint: config.baseEndpoint,
        csrfToken: config.csrfToken,
        drivers: [],
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },
        loading: false,
        showFilters: false,
        search: '',
        accountStatusFilter: '',
        accountStatusFilterName: 'All account statuses',
        availabilityFilter: '',
        availabilityFilterName: 'All availability',
        capabilityFilter: '',
        capabilityFilterName: 'All capabilities',
        createdFrom: '',
        createdTo: '',
        dateRangePicker: null,
        perPage: 50,
        sortBy: 'created_at',
        sortDirection: 'desc',
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Phone' },
            { key: 'capabilities', label: 'Capabilities' },
            { key: 'vehicle_type', label: 'Vehicle Type' },
            { key: 'status', label: 'Availability' },
            { key: 'is_active', label: 'Account' },
            { key: 'assignments', label: 'Assignments' },
            { key: 'created_at', label: 'Created At' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            name: true,
            email: true,
            phone: true,
            capabilities: true,
            vehicle_type: true,
            status: true,
            is_active: true,
            assignments: true,
            created_at: true,
            actions: true,
        },

        // Modal state
        showModal: false,
        modalMode: 'add', // 'add', 'edit', 'view'
        editingDriverId: null,
        saving: false,
        errors: {},
        form: {
            name: '',
            email: '',
            phone: '',
            profile_photo: null,
            photo_preview_url: '',
            password: '',
            password_confirmation: '',
            vehicle_type: '',
            vehicle_number: '',
            license_number: '',
            task_capabilities: ['pickup'],
            is_active: true,
        },

        init() {
            this.initDateRange();
            this.loadData();
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
                if (this.accountStatusFilter) params.append('account_status', this.accountStatusFilter);
                if (this.availabilityFilter) params.append('availability', this.availabilityFilter);
                if (this.capabilityFilter) params.append('capability', this.capabilityFilter);
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
                this.drivers = result.data;
                this.meta = {
                    current_page: result.meta.current_page,
                    from: result.meta.from,
                    to: result.meta.to,
                    total: result.meta.total,
                    last_page: result.meta.last_page,
                };
            } catch (error) {
                console.error('Error loading drivers:', error);
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

        setAccountStatusFilter(value, label) {
            this.accountStatusFilter = value;
            this.accountStatusFilterName = label;
            this.meta.current_page = 1;
            this.loadData();
        },

        clearAccountStatusFilter() {
            this.accountStatusFilter = '';
            this.accountStatusFilterName = 'All account statuses';
            this.meta.current_page = 1;
            this.loadData();
        },

        setAvailabilityFilter(value, label) {
            this.availabilityFilter = value;
            this.availabilityFilterName = label;
            this.meta.current_page = 1;
            this.loadData();
        },

        applyFilters() {
            this.accountStatusFilterName = this.accountStatusLabelForFilter(this.accountStatusFilter);
            this.availabilityFilterName = this.availabilityLabelForFilter(this.availabilityFilter);
            this.capabilityFilterName = this.capabilityLabelForFilter(this.capabilityFilter);
            this.meta.current_page = 1;
            this.loadData();
        },

        clearFilters() {
            this.search = '';
            this.accountStatusFilter = '';
            this.accountStatusFilterName = 'All account statuses';
            this.availabilityFilter = '';
            this.availabilityFilterName = 'All availability';
            this.capabilityFilter = '';
            this.capabilityFilterName = 'All capabilities';
            this.createdFrom = '';
            this.createdTo = '';
            if (this.$refs.createdRange) this.$refs.createdRange.value = '';
            this.meta.current_page = 1;
            this.loadData();
        },

        accountStatusLabelForFilter(value) {
            switch (value) {
                case 'active':
                    return 'Active';
                case 'inactive':
                    return 'Inactive';
                default:
                    return 'All account statuses';
            }
        },

        availabilityLabelForFilter(value) {
            switch (value) {
                case 'available':
                    return 'Available';
                case 'busy':
                    return 'Busy';
                case 'offline':
                    return 'Offline';
                default:
                    return 'All availability';
            }
        },

        capabilityLabelForFilter(value) {
            const labels = {
                pickup: 'Pickup',
                transport: 'Transport',
                delivery: 'Delivery',
                bus_handoff: 'Bus Handoff',
            };

            return labels[value] || 'All capabilities';
        },

        clearFilter(key) {
            if (key === 'all') {
                this.search = '';
                this.accountStatusFilter = '';
                this.accountStatusFilterName = 'All account statuses';
                this.availabilityFilter = '';
                this.availabilityFilterName = 'All availability';
                this.capabilityFilter = '';
                this.capabilityFilterName = 'All capabilities';
                this.createdFrom = '';
                this.createdTo = '';
                if (this.$refs.createdRange) this.$refs.createdRange.value = '';
            }

            if (key === 'account_status') {
                this.accountStatusFilter = '';
                this.accountStatusFilterName = 'All account statuses';
            }

            if (key === 'availability') {
                this.availabilityFilter = '';
                this.availabilityFilterName = 'All availability';
            }

            if (key === 'capability') {
                this.capabilityFilter = '';
                this.capabilityFilterName = 'All capabilities';
            }

            if (key === 'date') {
                this.createdFrom = '';
                this.createdTo = '';
                if (this.$refs.createdRange) this.$refs.createdRange.value = '';
            }

            this.meta.current_page = 1;
            this.loadData();
        },

        activeFilterChips() {
            const chips = [];
            if (this.accountStatusFilter) chips.push({ key: 'account_status', label: `Account: ${this.accountStatusFilterName}` });
            if (this.availabilityFilter) chips.push({ key: 'availability', label: `Availability: ${this.availabilityFilterName}` });
            if (this.capabilityFilter) chips.push({ key: 'capability', label: `Capability: ${this.capabilityFilterName}` });
            if (this.createdFrom || this.createdTo) chips.push({ key: 'date', label: `Created: ${this.createdFrom || '...'} - ${this.createdTo || '...'}` });
            return chips;
        },

        activeCount() {
            return this.drivers.filter((driver) => driver.is_active).length;
        },

        inactiveCount() {
            return this.drivers.filter((driver) => !driver.is_active).length;
        },

        availableCount() {
            return this.drivers.filter((driver) => driver.status === 'available').length;
        },

        driverStatusLabel(driver) {
            const value = driver.status || 'offline';
            return value.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
        },

        driverStatusBadgeClass(driver) {
            if (driver.status === 'available') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
            if (driver.status === 'busy') return 'border-amber-200 bg-amber-50 text-amber-700';
            return 'border-slate-200 bg-slate-50 text-slate-600';
        },

        activeBadgeClass(driver) {
            return driver.is_active
                ? 'border-orange-200 bg-orange-50 text-orange-700'
                : 'border-amber-200 bg-amber-50 text-amber-700';
        },

        capabilitySummary(driver) {
            return this.capabilityList(driver).map((capability) => capability.label).join(', ') || 'No capabilities';
        },

        capabilityList(driver) {
            const labels = {
                pickup: 'Pickup',
                transport: 'Transport',
                delivery: 'Delivery',
                bus_handoff: 'Bus Handoff',
            };

            const capabilities = Array.isArray(driver.task_capabilities) ? driver.task_capabilities : [];
            if (!capabilities.length) {
                return [{ value: 'none', label: 'No capabilities' }];
            }

            return capabilities.map((capability) => ({
                value: capability,
                label: labels[capability] || capability.replace(/_/g, ' '),
            }));
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

        clearDateFilter() {
            this.createdFrom = '';
            this.createdTo = '';
            if (this.dateRangePicker) {
                this.dateRangePicker.setStartDate(window.moment());
                this.dateRangePicker.setEndDate(window.moment());
            }
            if (this.$refs.createdRange) {
                this.$refs.createdRange.value = '';
            }
            this.loadData();
        },

        setPerPage(value) {
            this.perPage = value;
            this.meta.current_page = 1;
            this.loadData();
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
            this.editingDriverId = null;
            this.errors = {};
            this.form = {
                name: '',
                email: '',
                phone: '',
                profile_photo: null,
                photo_preview_url: '',
                password: '',
                password_confirmation: '',
                vehicle_type: '',
                vehicle_number: '',
                license_number: '',
                task_capabilities: ['pickup'],
                is_active: true,
            };
            this.showModal = true;
            this.$nextTick(() => {
                if (this.$refs.driverPhotoInput) this.$refs.driverPhotoInput.value = '';
            });
        },

        openEditModal(driver) {
            this.modalMode = 'edit';
            this.editingDriverId = driver.id;
            this.errors = {};
            this.form = {
                name: driver.name,
                email: driver.email,
                phone: driver.phone,
                profile_photo: null,
                photo_preview_url: driver.photo_url || '',
                password: '',
                password_confirmation: '',
                vehicle_type: driver.vehicle_type || '',
                vehicle_number: driver.vehicle_number || '',
                license_number: driver.license_number || '',
                task_capabilities: Array.isArray(driver.task_capabilities) && driver.task_capabilities.length
                    ? driver.task_capabilities
                    : ['pickup'],
                is_active: driver.is_active,
            };
            this.showModal = true;
            this.$nextTick(() => {
                if (this.$refs.driverPhotoInput) this.$refs.driverPhotoInput.value = '';
            });
        },

        viewDriver(driver) {
            this.modalMode = 'view';
            this.editingDriverId = driver.id;
            this.errors = {};
            this.form = {
                name: driver.name,
                email: driver.email,
                phone: driver.phone,
                profile_photo: null,
                photo_preview_url: driver.photo_url || '',
                password: '',
                password_confirmation: '',
                vehicle_type: driver.vehicle_type || '',
                vehicle_number: driver.vehicle_number || '',
                license_number: driver.license_number || '',
                task_capabilities: Array.isArray(driver.task_capabilities) && driver.task_capabilities.length
                    ? driver.task_capabilities
                    : ['pickup'],
                is_active: driver.is_active,
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.errors = {};
            if (this.form.photo_preview_url?.startsWith('blob:')) {
                URL.revokeObjectURL(this.form.photo_preview_url);
            }
        },

        handleDriverPhoto(event) {
            const file = event.target.files?.[0] || null;
            this.form.profile_photo = file;
            if (this.form.photo_preview_url?.startsWith('blob:')) {
                URL.revokeObjectURL(this.form.photo_preview_url);
            }
            this.form.photo_preview_url = file ? URL.createObjectURL(file) : '';
        },

        clearSelectedPhoto() {
            this.form.profile_photo = null;
            if (this.form.photo_preview_url?.startsWith('blob:')) {
                URL.revokeObjectURL(this.form.photo_preview_url);
            }
            const driver = this.drivers.find((row) => Number(row.id) === Number(this.editingDriverId));
            this.form.photo_preview_url = driver?.photo_url || '';
            if (this.$refs.driverPhotoInput) {
                this.$refs.driverPhotoInput.value = '';
            }
        },

        async saveDriver() {
            this.saving = true;
            this.errors = {};

            try {
                const url = this.modalMode === 'add'
                    ? this.storeEndpoint
                    : `${this.baseEndpoint}/${this.editingDriverId}`;

                const body = new FormData();
                if (this.modalMode === 'edit') {
                    body.append('_method', 'PUT');
                }
                body.append('name', this.form.name || '');
                body.append('email', this.form.email || '');
                body.append('phone', this.form.phone || '');
                body.append('vehicle_type', this.form.vehicle_type || '');
                body.append('vehicle_number', this.form.vehicle_number || '');
                body.append('license_number', this.form.license_number || '');
                body.append('is_active', this.form.is_active ? '1' : '0');
                (this.form.task_capabilities || []).forEach((capability) => {
                    body.append('task_capabilities[]', capability);
                });
                if (this.modalMode === 'add' || this.form.password) {
                    body.append('password', this.form.password || '');
                    body.append('password_confirmation', this.form.password_confirmation || '');
                }
                if (this.form.profile_photo) {
                    body.append('profile_photo', this.form.profile_photo);
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });

                const result = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        this.errors = result.errors || {};
                    } else {
                        throw new Error(result.message || 'Failed to save rider');
                    }
                    return;
                }

                this.closeModal();
                this.loadData();
            } catch (error) {
                console.error('Error saving rider:', error);
            } finally {
                this.saving = false;
            }
        },

        async toggleDriverStatus(driver) {
            try {
                const response = await fetch(`${this.baseEndpoint}/${driver.id}/toggle-active`, {
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
                console.error('Failed to update rider status:', err);
            }
        },

        openDeleteModal(driver) {
            if (this.$store?.driversDelete) {
                this.$store.driversDelete.driver = driver;
                this.$store.driversDelete.onConfirm = () => this.confirmDelete();
                this.$store.driversDelete.show = true;
            }
        },

        async confirmDelete() {
            const store = this.$store?.driversDelete;
            if (!store?.driver) return;

            store.deleting = true;

            try {
                const response = await fetch(`${this.baseEndpoint}/${store.driver.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to delete rider');
                }

                store.show = false;
                store.driver = null;
                this.loadData();
            } catch (error) {
                console.error('Error deleting rider:', error);
                alert('Failed to delete rider. Please try again.');
            } finally {
                store.deleting = false;
            }
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.accountStatusFilter) params.append('account_status', this.accountStatusFilter);
                if (this.availabilityFilter) params.append('availability', this.availabilityFilter);
                if (this.capabilityFilter) params.append('capability', this.capabilityFilter);
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
                if (this.accountStatusFilter) params.append('account_status', this.accountStatusFilter);
                if (this.availabilityFilter) params.append('availability', this.availabilityFilter);
                if (this.capabilityFilter) params.append('capability', this.capabilityFilter);
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

            doc.title = 'Riders Export';
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
            title.textContent = 'Riders List';
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

            this.downloadFile(csvContent, 'riders-drivers.csv', 'text/csv');
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

function getDriversConfig() {
    const container = document.querySelector('[data-drivers-config]');
    let config = window.driversTableConfig || null;

    if (container) {
        try {
            config = JSON.parse(container.getAttribute('data-drivers-config'));
        } catch (error) {
            console.error('Invalid drivers config JSON:', error);
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

function registerDriversTable() {
    if (!window.Alpine) {
        return;
    }

    const config = getDriversConfig();
    if (!config) {
        return;
    }

    if (!window.driversTable) {
        window.driversTable = () => buildDriversTable(config);
    }

    Alpine.store('driversDelete', {
        show: false,
        driver: null,
        deleting: false,
        onConfirm: null,
    });

    Alpine.data('driversTable', window.driversTable);
}

if (window.Alpine) {
    registerDriversTable();
} else {
    document.addEventListener('alpine:init', registerDriversTable);
}
