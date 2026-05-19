/**
 * Admin users page Alpine component
 * Loaded via Vite bundle (resources/js/admin/app.js)
 */

function ensureDateRangeDependencies() {
    const cssId = 'daterangepicker-css';
    if (!document.getElementById(cssId)) {
        const link = document.createElement('link');
        link.id = cssId;
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
        document.head.appendChild(link);
    }

    const loadScript = (id, src) => new Promise((resolve) => {
        if (document.getElementById(id)) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.id = id;
        script.src = src;
        script.onload = () => resolve();
        document.body.appendChild(script);
    });

    return loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
        .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
        .then(() => {
            window.$ = window.jQuery = window.jQuery || window.$;
            window.moment = window.moment || moment;
            return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
        });
}

document.addEventListener('alpine:init', () => {
    Alpine.data('usersTable', () => ({
        endpoint: '',
        exportEndpoint: '',
        storeEndpoint: '',
        csrfToken: '',
        users: [],
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },
        loading: false,
        search: '',
        roleFilter: '',
        roleFilterName: 'All roles',
        statusFilter: '',
        statusFilterName: 'All statuses',
        loginStateFilter: '',
        loginStateFilterName: 'All login states',
        emailFilter: '',
        phoneFilter: '',
        createdByFilter: '',
        showFilters: false,
        createdFrom: '',
        createdTo: '',
        lastLoginFrom: '',
        lastLoginTo: '',
        dateRangePicker: null,
        lastLoginDateRangePicker: null,
        perPage: 50,
        sortBy: 'created_at',
        sortDirection: 'desc',
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'role', label: 'Role' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Phone' },
            { key: 'status', label: 'Status' },
            { key: 'created_at', label: 'Created At' },
            { key: 'last_login_at', label: 'Last Login' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            name: true,
            role: true,
            email: true,
            phone: true,
            status: true,
            created_at: true,
            last_login_at: true,
            actions: true,
        },

        // Modal state
        showModal: false,
        showDeleteModal: false,
        modalMode: 'create',
        editingUser: null,
        deletingUser: null,
        submitting: false,
        deleting: false,
        changePassword: false,
        exporting: false,
        formData: {
            name: '',
            email: '',
            phone: '',
            role_id: '',
            is_active: '1',
            password: '',
            password_confirmation: '',
        },
        formErrors: {},

        init() {
            const dataset = this.$el?.dataset || {};
            this.endpoint = dataset.endpoint || '';
            this.exportEndpoint = dataset.exportEndpoint || '';
            this.storeEndpoint = dataset.storeEndpoint || '';
            this.csrfToken = dataset.csrfToken || '';

            if (!this.endpoint || !this.exportEndpoint || !this.storeEndpoint || !this.csrfToken) {
                console.error('Users table config missing on root element data attributes.');
                if (window.showToast) {
                    window.showToast('Users page configuration is missing.', 'error');
                }
                return;
            }

            this.initDateRange();
            this.loadData();
        },

        // Modal methods
        openCreateModal() {
            this.modalMode = 'create';
            this.editingUser = null;
            this.changePassword = false;
            this.formData = {
                name: '',
                email: '',
                phone: '',
                role_id: '',
                is_active: '1',
                password: '',
                password_confirmation: '',
            };
            this.formErrors = {};
            this.showModal = true;
        },

        openEditModal(user) {
            this.modalMode = 'edit';
            this.editingUser = user;
            this.changePassword = false;
            this.formData = {
                name: user.name,
                email: user.email,
                phone: user.phone || '',
                role_id: (user.roles && user.roles.length) ? String(user.roles[0].id) : '',
                is_active: user.is_active ? '1' : '0',
                password: '',
                password_confirmation: '',
            };
            this.formErrors = {};
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingUser = null;
            this.formErrors = {};
        },

        openDeleteModal(user) {
            if (!user || !user.can_delete || user.is_self) return;
            this.deletingUser = user;
            this.showDeleteModal = true;
        },

        closeDeleteModal(force = false) {
            if (this.deleting && !force) return;
            this.showDeleteModal = false;
            this.deletingUser = null;
        },

        async deleteUser() {
            if (!this.deletingUser || !this.deletingUser.delete_url) return;

            this.deleting = true;
            try {
                const response = await fetch(this.deletingUser.delete_url, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result.message || 'Failed to delete user.');
                }

                this.closeDeleteModal(true);
                await this.loadData();
                if (window.showToast) {
                    window.showToast(result.message || 'User deleted successfully.', 'success');
                }
            } catch (error) {
                console.error('Delete user failed:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to delete user.', 'error');
                }
            } finally {
                this.deleting = false;
            }
        },

        async submitForm() {
            this.submitting = true;
            this.formErrors = {};

            try {
                const url = this.modalMode === 'create'
                    ? this.storeEndpoint
                    : this.editingUser.edit_url.replace('/edit', '');

                const method = this.modalMode === 'create' ? 'POST' : 'PUT';

                const body = {
                    name: this.formData.name,
                    email: this.formData.email,
                    phone: this.formData.phone,
                    role_id: this.formData.role_id || null,
                };

                if (this.modalMode === 'edit' && !this.editingUser?.is_self) {
                    body.is_active = this.formData.is_active;
                }

                if (this.modalMode === 'create' || this.changePassword) {
                    body.password = this.formData.password;
                    body.password_confirmation = this.formData.password_confirmation;
                }

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });

                const result = await response.json();

                if (!response.ok) {
                    if (response.status === 422 && result.errors) {
                        this.formErrors = {};
                        for (const [key, messages] of Object.entries(result.errors)) {
                            this.formErrors[key] = messages[0];
                        }
                    } else {
                        this.formErrors.general = result.message || 'An error occurred';
                    }
                    return;
                }

                this.closeModal();
                await this.loadData();
                if (window.showToast) {
                    window.showToast(
                        result.message || (this.modalMode === 'create' ? 'User created successfully.' : 'User updated successfully.'),
                        'success'
                    );
                }
            } catch (error) {
                console.error('Form submission error:', error);
                this.formErrors.general = 'An unexpected error occurred';
                if (window.showToast) {
                    window.showToast(this.formErrors.general, 'error');
                }
            } finally {
                this.submitting = false;
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
                if (this.roleFilter) params.append('role', this.roleFilter);
                if (this.statusFilter !== '' && this.statusFilter !== null) params.append('status', this.statusFilter);
                if (this.loginStateFilter) params.append('login_state', this.loginStateFilter);
                if (this.emailFilter) params.append('email', this.emailFilter);
                if (this.phoneFilter) params.append('phone', this.phoneFilter);
                if (this.createdByFilter) params.append('created_by', this.createdByFilter);
                if (this.createdFrom) params.append('date_from', this.createdFrom);
                if (this.createdTo) params.append('date_to', this.createdTo);
                if (this.lastLoginFrom) params.append('last_login_from', this.lastLoginFrom);
                if (this.lastLoginTo) params.append('last_login_to', this.lastLoginTo);

                const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch data');

                const result = await response.json();
                this.users = result.data;
                this.meta = {
                    current_page: result.meta.current_page,
                    from: result.meta.from,
                    to: result.meta.to,
                    total: result.meta.total,
                    last_page: result.meta.last_page,
                };
            } catch (error) {
                console.error('Error loading users:', error);
                if (window.showToast) {
                    window.showToast('Failed to load users.', 'error');
                }
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

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        initDateRange() {
            if (!this.$refs.createdRange && !this.$refs.lastLoginRange) return;

            const setupPicker = (refName, fromKey, toKey, pickerKey) => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker || !this.$refs[refName]) return;

                const $input = window.$(this.$refs[refName]);

                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'right',
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: 'Clear',
                    },
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
                    this[fromKey] = picker.startDate.format('YYYY-MM-DD');
                    this[toKey] = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this[fromKey]} - ${this[toKey]}`);
                });

                $input.on('cancel.daterangepicker', () => {
                    this.clearDateFilter(fromKey, toKey, refName, pickerKey);
                });

                this[pickerKey] = $input.data('daterangepicker');
            };

            const setupPickers = () => {
                setupPicker('createdRange', 'createdFrom', 'createdTo', 'dateRangePicker');
                setupPicker('lastLoginRange', 'lastLoginFrom', 'lastLoginTo', 'lastLoginDateRangePicker');
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPickers();
                return;
            }

            ensureDateRangeDependencies()
                .then(() => {
                    window.$ = window.jQuery = window.$ || window.jQuery;
                    window.moment = window.moment || moment;
                    setupPickers();
                })
                .catch((error) => {
                    console.error('Failed to initialize date range picker:', error);
                });
        },

        clearDateFilter(fromKey = 'createdFrom', toKey = 'createdTo', refName = 'createdRange', pickerKey = 'dateRangePicker') {
            this[fromKey] = '';
            this[toKey] = '';
            if (this[pickerKey] && window.moment) {
                this[pickerKey].setStartDate(window.moment());
                this[pickerKey].setEndDate(window.moment());
            }
            if (this.$refs[refName]) {
                this.$refs[refName].value = '';
            }
        },

        applyFilters() {
            this.meta.current_page = 1;
            this.loadData();
        },

        clearFilters() {
            this.search = '';
            this.roleFilter = '';
            this.roleFilterName = 'All roles';
            this.statusFilter = '';
            this.statusFilterName = 'All statuses';
            this.loginStateFilter = '';
            this.loginStateFilterName = 'All login states';
            this.emailFilter = '';
            this.phoneFilter = '';
            this.createdByFilter = '';
            this.createdFrom = '';
            this.createdTo = '';
            this.lastLoginFrom = '';
            this.lastLoginTo = '';
            if (this.$refs.createdRange) {
                this.$refs.createdRange.value = '';
            }
            if (this.$refs.lastLoginRange) {
                this.$refs.lastLoginRange.value = '';
            }
            this.meta.current_page = 1;
            this.loadData();
        },

        activeFilterChips() {
            const chips = [];
            if (this.roleFilter) chips.push({ key: 'role', label: this.roleFilterName });
            if (this.statusFilter !== '' && this.statusFilter !== null) chips.push({ key: 'status', label: this.statusFilterName });
            if (this.loginStateFilter) chips.push({ key: 'login_state', label: this.loginStateFilterName });
            if (this.emailFilter) chips.push({ key: 'email', label: `Email: ${this.emailFilter}` });
            if (this.phoneFilter) chips.push({ key: 'phone', label: `Phone: ${this.phoneFilter}` });
            if (this.createdByFilter) chips.push({ key: 'created_by', label: `Created by: ${this.createdByFilter}` });
            if (this.createdFrom && this.createdTo) chips.push({ key: 'date', label: `Created: ${this.createdFrom} - ${this.createdTo}` });
            if (this.lastLoginFrom && this.lastLoginTo) chips.push({ key: 'last_login', label: `Last login: ${this.lastLoginFrom} - ${this.lastLoginTo}` });
            return chips;
        },

        clearFilter(key) {
            if (key === 'role') {
                this.roleFilter = '';
                this.roleFilterName = 'All roles';
            }
            if (key === 'status') {
                this.statusFilter = '';
                this.statusFilterName = 'All statuses';
            }
            if (key === 'login_state') {
                this.loginStateFilter = '';
                this.loginStateFilterName = 'All login states';
            }
            if (key === 'email') this.emailFilter = '';
            if (key === 'phone') this.phoneFilter = '';
            if (key === 'created_by') this.createdByFilter = '';
            if (key === 'date') {
                this.createdFrom = '';
                this.createdTo = '';
                if (this.$refs.createdRange) this.$refs.createdRange.value = '';
            }
            if (key === 'last_login') {
                this.lastLoginFrom = '';
                this.lastLoginTo = '';
                if (this.$refs.lastLoginRange) this.$refs.lastLoginRange.value = '';
            }
            this.applyFilters();
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

        async toggleUserStatus(user) {
            if (user.is_self) return;

            try {
                const response = await fetch(user.toggle_url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(result.message || 'Failed to update user status.');
                }

                user.is_active = !user.is_active;
                await this.loadData();

                if (window.showToast) {
                    window.showToast(result.message || 'User status updated successfully.', 'success');
                }
            } catch (err) {
                console.error('Failed to update user status:', err);
                if (window.showToast) {
                    window.showToast(err.message || 'Failed to update user status.', 'error');
                }
            }
        },

        async exportData(format) {
            this.exporting = true;

            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.roleFilter) params.append('role', this.roleFilter);
                if (this.statusFilter !== '' && this.statusFilter !== null) {
                    params.append('status', this.statusFilter);
                }
                if (this.loginStateFilter) params.append('login_state', this.loginStateFilter);
                if (this.emailFilter) params.append('email', this.emailFilter);
                if (this.phoneFilter) params.append('phone', this.phoneFilter);
                if (this.createdByFilter) params.append('created_by', this.createdByFilter);
                if (this.createdFrom) params.append('date_from', this.createdFrom);
                if (this.createdTo) params.append('date_to', this.createdTo);
                if (this.lastLoginFrom) params.append('last_login_from', this.lastLoginFrom);
                if (this.lastLoginTo) params.append('last_login_to', this.lastLoginTo);
                params.append('format', format);

                if (format === 'excel' || format === 'pdf') {
                    const url = `${this.exportEndpoint}?${params.toString()}`;
                    window.location.href = url;
                    setTimeout(() => {
                        this.exporting = false;
                    }, 1000);
                    return;
                }

                const response = await fetch(`${this.exportEndpoint}?${params.toString()}`, {
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
                if (window.showToast) {
                    window.showToast('Export failed. Please try again.', 'error');
                }
            } finally {
                this.exporting = false;
            }
        },

        async printData() {
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.roleFilter) params.append('role', this.roleFilter);
                if (this.statusFilter !== '' && this.statusFilter !== null) {
                    params.append('status', this.statusFilter);
                }
                if (this.loginStateFilter) params.append('login_state', this.loginStateFilter);
                if (this.emailFilter) params.append('email', this.emailFilter);
                if (this.phoneFilter) params.append('phone', this.phoneFilter);
                if (this.createdByFilter) params.append('created_by', this.createdByFilter);
                if (this.createdFrom) params.append('date_from', this.createdFrom);
                if (this.createdTo) params.append('date_to', this.createdTo);
                if (this.lastLoginFrom) params.append('last_login_from', this.lastLoginFrom);
                if (this.lastLoginTo) params.append('last_login_to', this.lastLoginTo);

                const response = await fetch(`${this.exportEndpoint}?${params.toString()}`, {
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
                if (window.showToast) {
                    window.showToast('Print failed. Please try again.', 'error');
                }
            }
        },

        openPrintWindow(data) {
            if (!data.length) {
                if (window.showToast) {
                    window.showToast('No data to print.', 'warning');
                }
                return;
            }

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                if (window.showToast) {
                    window.showToast('Pop-up blocked. Please allow pop-ups to print.', 'warning');
                }
                return;
            }

            const doc = printWindow.document;
            const headers = Object.keys(data[0]);

            if (!doc.documentElement) {
                doc.appendChild(doc.createElement('html'));
            }
            if (!doc.head) {
                doc.documentElement.appendChild(doc.createElement('head'));
            }
            if (!doc.body) {
                doc.documentElement.appendChild(doc.createElement('body'));
            }
            doc.title = 'Users Export';
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
            title.textContent = 'Users List';
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

            setTimeout(() => {
                printWindow.print();
            }, 250);
        },

        downloadCSV(data) {
            if (!data.length) return;

            const headers = Object.keys(data[0]);
            let csvContent = `${headers.join(',')}\n`;

            data.forEach((row) => {
                const rowData = headers.map((header) => {
                    let cell = row[header] ?? '';
                    cell = String(cell).replace(/"/g, '""');
                    return `"${cell}"`;
                });
                csvContent += `${rowData.join(',')}\n`;
            });

            this.downloadFile(csvContent, 'users.csv', 'text/csv');
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
    }));
});
