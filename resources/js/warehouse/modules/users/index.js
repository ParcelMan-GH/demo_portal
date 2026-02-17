function getWarehouseUsersConfig() {
    const container = document.querySelector('[data-warehouse-users-config]');
    if (!container) return null;

    try {
        const config = JSON.parse(container.getAttribute('data-warehouse-users-config') || '{}');
        if (!config.csrfToken) {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            config.csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        }
        return config;
    } catch (error) {
        console.error('Invalid warehouse users config JSON:', error);
        return null;
    }
}

function buildWarehouseUsersPage(config) {
    return {
        endpoint: config.endpoint,
        exportEndpoint: config.exportEndpoint,
        storeEndpoint: config.storeEndpoint,
        updateEndpointTemplate: config.updateEndpointTemplate,
        toggleEndpointTemplate: config.toggleEndpointTemplate,
        csrfToken: config.csrfToken,
        roles: Array.isArray(config.roles) ? config.roles : [],
        permissions: config.permissions || {},

        users: [],
        loading: false,
        search: '',
        roleFilter: '',
        statusFilter: '',
        statusFilterName: 'All statuses',
        perPage: 10,
        sortBy: 'created_at',
        sortDirection: 'desc',
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },

        visibleColumns: {
            name: true,
            role: true,
            email: true,
            status: true,
            created_at: true,
            last_login_at: true,
            actions: true,
        },
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'role', label: 'Role' },
            { key: 'email', label: 'Email' },
            { key: 'status', label: 'Status' },
            { key: 'created_at', label: 'Created At' },
            { key: 'last_login_at', label: 'Last Login' },
            { key: 'actions', label: 'Actions' },
        ],

        showModal: false,
        modalMode: 'create',
        submitting: false,
        editingUserId: null,
        errors: {},
        form: {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            role_id: '',
            is_active: true,
        },

        init() {
            this.loadData();
        },

        selectedRoleName() {
            if (!this.roleFilter) return 'All roles';
            const selected = this.roles.find((role) => String(role.id) === String(this.roleFilter));
            return selected ? selected.name : 'All roles';
        },

        setStatusFilter(value, label) {
            this.statusFilter = value;
            this.statusFilterName = label;
            this.meta.current_page = 1;
            this.loadData();
        },

        setPerPage(value) {
            const parsed = Number(value);
            if (!Number.isFinite(parsed) || parsed <= 0) return;
            this.perPage = parsed;
            this.meta.current_page = 1;
            this.loadData();
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
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
                if (this.statusFilter !== '') params.append('status', this.statusFilter);

                const response = await fetch(`${this.endpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch warehouse users');
                const result = await response.json();

                this.users = Array.isArray(result.data) ? result.data : [];
                this.meta = result.meta || this.meta;
            } catch (error) {
                console.error(error);
                window.showToast?.('Failed to load warehouse users.', 'error');
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

        firstPage() {
            if (this.meta.current_page !== 1) {
                this.meta.current_page = 1;
                this.loadData();
            }
        },

        previousPage() {
            if (this.meta.current_page > 1) {
                this.meta.current_page -= 1;
                this.loadData();
            }
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) {
                this.meta.current_page += 1;
                this.loadData();
            }
        },

        lastPage() {
            if (this.meta.current_page !== this.meta.last_page) {
                this.meta.current_page = this.meta.last_page;
                this.loadData();
            }
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        openCreateModal() {
            if (!this.permissions.can_create) return;
            this.modalMode = 'create';
            this.editingUserId = null;
            this.errors = {};
            this.form = {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                role_id: '',
                is_active: true,
            };
            this.showModal = true;
        },

        openEditModal(user) {
            if (!this.permissions.can_edit || !user?.can_manage) return;

            this.modalMode = 'edit';
            this.editingUserId = user.id;
            this.errors = {};
            this.form = {
                name: user.name || '',
                email: user.email || '',
                password: '',
                password_confirmation: '',
                role_id: user.roles && user.roles.length ? user.roles[0].id : '',
                is_active: Boolean(user.is_active),
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingUserId = null;
            this.submitting = false;
            this.errors = {};
        },

        async submitForm() {
            this.submitting = true;
            this.errors = {};

            const isCreate = this.modalMode === 'create';
            const endpoint = isCreate
                ? this.storeEndpoint
                : this.updateEndpointTemplate.replace('__ID__', String(this.editingUserId));

            const payload = {
                name: this.form.name,
                email: this.form.email,
                role_id: this.form.role_id || null,
                is_active: Boolean(this.form.is_active),
            };

            if (this.form.password) {
                payload.password = this.form.password;
                payload.password_confirmation = this.form.password_confirmation;
            } else if (isCreate) {
                payload.password = '';
                payload.password_confirmation = '';
            }

            try {
                const response = await fetch(endpoint, {
                    method: isCreate ? 'POST' : 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && result.errors) {
                        this.errors = result.errors;
                    } else {
                        this.errors = { general: [result.message || 'Failed to save warehouse user.'] };
                    }
                    return;
                }

                this.closeModal();
                this.meta.current_page = 1;
                await this.loadData();
                window.showToast?.(isCreate ? 'Warehouse user created successfully.' : 'Warehouse user updated successfully.', 'success');
            } catch (error) {
                console.error(error);
                this.errors = { general: ['Failed to save warehouse user.'] };
            } finally {
                this.submitting = false;
            }
        },

        async toggleUserStatus(user) {
            if (!this.permissions.can_deactivate || !user?.can_manage || user?.is_self) return;

            const endpoint = this.toggleEndpointTemplate.replace('__ID__', String(user.id));
            try {
                const response = await fetch(endpoint, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    window.showToast?.(result.message || 'Unable to update user status.', 'error');
                    return;
                }

                await this.loadData();
                window.showToast?.(result.message || 'User status updated successfully.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.('Unable to update user status.', 'error');
            }
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.roleFilter) params.append('role', this.roleFilter);
                if (this.statusFilter !== '') params.append('status', this.statusFilter);
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
                    this.downloadCSV(result.data || []);
                } else if (format === 'print') {
                    this.openPrintWindow(result.data || []);
                }
            } catch (error) {
                console.error(error);
                window.showToast?.('Export failed.', 'error');
            }
        },

        downloadCSV(data) {
            if (!data.length) return;
            const headers = Object.keys(data[0]);
            const csv = [
                headers.join(','),
                ...data.map((row) =>
                    headers
                        .map((header) => `"${String(row[header] ?? '').replace(/"/g, '""')}"`)
                        .join(',')
                ),
            ].join('\n');

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'warehouse-users.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        openPrintWindow(data) {
            if (!data.length) {
                window.showToast?.('No data to print.', 'warning');
                return;
            }

            const printWindow = window.open('', '_blank');
            if (!printWindow) return;

            const headers = Object.keys(data[0]);
            const rowsHtml = data
                .map((row) => `<tr>${headers.map((h) => `<td>${row[h] ?? '-'}</td>`).join('')}</tr>`)
                .join('');

            printWindow.document.write(`
                <html>
                <head>
                    <title>Warehouse Users</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
                        th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; text-align: left; }
                        th { background: #f5f5f5; }
                    </style>
                </head>
                <body>
                    <h2>Warehouse Users</h2>
                    <table>
                        <thead><tr>${headers.map((h) => `<th>${h}</th>`).join('')}</tr></thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                </body>
                </html>
            `);
            printWindow.document.close();
            setTimeout(() => printWindow.print(), 250);
        },
    };
}

function registerWarehouseUsersPage() {
    if (!window.Alpine) return;

    const config = getWarehouseUsersConfig();
    if (!config) return;

    window.warehouseUsersPage = () => buildWarehouseUsersPage(config);
    Alpine.data('warehouseUsersPage', window.warehouseUsersPage);
}

if (window.Alpine) {
    registerWarehouseUsersPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseUsersPage);
}

