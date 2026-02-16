/**
 * Admin warehouse show page Alpine component
 * Extracted from Blade inline scripts and bundled via Vite.
 */

function warehouseUsersTable(config) {
    return {
        endpoint: config.endpoint,
        exportEndpoint: config.exportEndpoint,
        createEndpoint: config.createEndpoint,
        warehouseId: Number(config.warehouseId),
        csrfToken: config.csrfToken,
        canCreateUsers: Boolean(config.canCreateUsers),
        roles: Array.isArray(config.roles) ? config.roles : [],

        showUserModal: false,
        userModalMode: 'create',
        submittingUser: false,
        selectedUser: null,
        userFormErrors: {},
        userForm: {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            role_id: null,
            is_active: '1',
        },

        users: [],
        loading: false,
        search: '',
        roleFilter: '',
        statusFilter: '',
        sortBy: 'created_at',
        sortDirection: 'desc',
        perPage: 10,
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

        init() {
            this.loadData();
        },

        selectedRoleName() {
            if (!this.roleFilter) return 'All roles';
            const selected = this.roles.find((role) => String(role.id) === String(this.roleFilter));
            return selected ? selected.name : 'All roles';
        },

        openCreateModal() {
            this.userModalMode = 'create';
            this.selectedUser = null;
            this.userFormErrors = {};
            this.userForm = {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                role_id: null,
                is_active: '1',
            };
            this.showUserModal = true;
        },

        openEditModal(user) {
            if (!user || !user.can_manage) return;

            this.userModalMode = 'edit';
            this.selectedUser = user;
            this.userFormErrors = {};
            this.userForm = {
                name: user.name || '',
                email: user.email || '',
                password: '',
                password_confirmation: '',
                role_id: Array.isArray(user.roles) && user.roles.length ? Number(user.roles[0].id) : null,
                is_active: user.is_active ? '1' : '0',
            };
            this.showUserModal = true;
        },

        closeUserModal() {
            this.showUserModal = false;
            this.submittingUser = false;
            this.selectedUser = null;
            this.userFormErrors = {};
        },

        async submitUserForm() {
            this.submittingUser = true;
            this.userFormErrors = {};

            const isCreate = this.userModalMode === 'create';
            const updateEndpoint = this.selectedUser?.update_url
                || (this.selectedUser?.edit_url ? this.selectedUser.edit_url.replace(/\/edit$/, '') : null);
            const endpoint = isCreate ? this.createEndpoint : updateEndpoint;

            if (!endpoint) {
                this.userFormErrors.general = 'Unable to resolve user endpoint.';
                this.submittingUser = false;
                return;
            }

            const payload = {
                name: this.userForm.name,
                email: this.userForm.email,
                role_id: this.userForm.role_id,
                warehouse_id: this.warehouseId,
            };

            if (!isCreate && this.selectedUser && !this.selectedUser.is_self) {
                payload.is_active = this.userForm.is_active;
            }

            if (this.userForm.password) {
                payload.password = this.userForm.password;
                payload.password_confirmation = this.userForm.password_confirmation;
            } else if (isCreate) {
                payload.password = this.userForm.password;
                payload.password_confirmation = this.userForm.password_confirmation;
            }

            try {
                const response = await fetch(endpoint, {
                    method: isCreate ? 'POST' : 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422 && result.errors) {
                        Object.entries(result.errors).forEach(([field, messages]) => {
                            const key = field.startsWith('roles') ? 'roles' : field;
                            this.userFormErrors[key] = Array.isArray(messages) ? messages[0] : messages;
                        });
                    } else {
                        this.userFormErrors.general = result.message || 'Failed to save user.';
                    }
                    return;
                }

                this.closeUserModal();
                this.meta.current_page = 1;
                await this.loadData();
                if (window.showToast) {
                    window.showToast(isCreate ? 'User created successfully' : 'User updated successfully', 'success');
                }
            } catch (error) {
                console.error('Warehouse user save failed:', error);
                this.userFormErrors.general = error.message || 'Failed to save user.';
            } finally {
                this.submittingUser = false;
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
                if (this.statusFilter !== '') params.append('status', this.statusFilter);

                const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to load warehouse users');
                }

                const result = await response.json();
                this.users = Array.isArray(result.data) ? result.data : [];
                this.meta = result.meta || this.meta;
            } catch (error) {
                console.error('Warehouse users load failed:', error);
                this.users = [];
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to load warehouse users', 'error');
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

        firstPage() {
            if (this.meta.current_page <= 1) return;
            this.meta.current_page = 1;
            this.loadData();
        },

        previousPage() {
            if (this.meta.current_page <= 1) return;
            this.meta.current_page--;
            this.loadData();
        },

        nextPage() {
            if (this.meta.current_page >= this.meta.last_page) return;
            this.meta.current_page++;
            this.loadData();
        },

        lastPage() {
            if (this.meta.current_page >= this.meta.last_page) return;
            this.meta.current_page = this.meta.last_page;
            this.loadData();
        },

        async toggleUserStatus(user) {
            if (!user || !user.toggle_url || user.is_self || !user.can_manage) return;

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

                if (!response.ok) {
                    throw new Error('Failed to update user status');
                }

                await this.loadData();
                if (window.showToast) {
                    window.showToast('User status updated successfully', 'success');
                }
            } catch (error) {
                console.error('Warehouse user status update failed:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to update user status', 'error');
                }
            }
        },

        async exportData(format) {
            const params = new URLSearchParams();
            if (this.search) params.append('search', this.search);
            if (this.roleFilter) params.append('role', this.roleFilter);
            if (this.statusFilter !== '') params.append('status', this.statusFilter);
            params.append('format', format);

            if (format === 'excel' || format === 'pdf') {
                window.location.href = `${this.exportEndpoint}?${params.toString()}`;
                return;
            }

            try {
                const response = await fetch(`${this.exportEndpoint}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to export users');
                }

                const result = await response.json();
                this.downloadCSV(Array.isArray(result.data) ? result.data : []);
            } catch (error) {
                console.error('Warehouse users export failed:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Export failed', 'error');
                }
            }
        },

        downloadCSV(data) {
            if (!data.length) {
                if (window.showToast) {
                    window.showToast('No data to export', 'warning');
                }
                return;
            }

            const headers = Object.keys(data[0]);
            let csvContent = headers.join(',') + '\n';

            data.forEach((row) => {
                const rowData = headers.map((header) => {
                    let cell = row[header] ?? '';
                    cell = String(cell).replace(/"/g, '""');
                    return `"${cell}"`;
                });
                csvContent += rowData.join(',') + '\n';
            });

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'warehouse-users.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },
    };
}

function warehouseShow() {
    return {
        config: {},
        warehouse: {},
        canManage: false,
        activeTab: 'details',

        showToggleModal: false,

        // Edit modal state
        showEditModal: false,
        saving: false,
        toggling: false,
        errors: {},
        form: {
            name: '',
            code: '',
            address: '',
            contact_phone: '',
            contact_email: '',
            capacity: '',
            is_active: true
        },

        init() {
            this.config = window.warehouseShowConfig;
            this.warehouse = this.config.warehouse;
            this.canManage = this.config.canManage;
        },

        tabClass(tab) {
            return this.activeTab === tab
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50';
        },

        openEditModal() {
            this.form = {
                name: this.warehouse.name,
                code: this.warehouse.code || '',
                address: this.warehouse.address || '',
                contact_phone: this.warehouse.contact_phone || '',
                contact_email: this.warehouse.contact_email || '',
                capacity: this.warehouse.capacity || '',
                is_active: this.warehouse.is_active
            };
            this.errors = {};
            this.showEditModal = true;
        },

        async saveWarehouse() {
            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch(this.config.updateEndpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        this.errors = data.errors || {};
                    } else {
                        throw new Error(data.message || 'Failed to update warehouse');
                    }
                    return;
                }

                // Update local warehouse data
                this.warehouse.name = this.form.name;
                this.warehouse.code = this.form.code;
                this.warehouse.address = this.form.address;
                this.warehouse.contact_phone = this.form.contact_phone;
                this.warehouse.contact_email = this.form.contact_email;
                this.warehouse.capacity = this.form.capacity;
                this.warehouse.is_active = this.form.is_active;

                this.showEditModal = false;

                // Show success notification
                if (window.showToast) {
                    window.showToast('Warehouse updated successfully', 'success');
                }
            } catch (error) {
                console.error('Save error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to update warehouse', 'error');
                }
            } finally {
                this.saving = false;
            }
        },

        async toggleActive() {
            this.toggling = true;

            try {
                const response = await fetch(this.config.toggleActiveEndpoint, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to toggle status');
                }

                this.warehouse.is_active = data.is_active;
                this.showToggleModal = false;

                if (window.showToast) {
                    window.showToast(data.message, 'success');
                }
            } catch (error) {
                console.error('Toggle error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to toggle status', 'error');
                }
            } finally {
                this.toggling = false;
            }
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}

function getWarehousePageConfigs() {
    const container = document.querySelector('[data-warehouse-show-config]');
    if (!container) return null;

    const rawShowConfig = container.getAttribute('data-warehouse-show-config');
    const rawUsersConfig = container.getAttribute('data-warehouse-users-config');

    if (!rawShowConfig || !rawUsersConfig) return null;

    try {
        return {
            show: JSON.parse(rawShowConfig),
            users: JSON.parse(rawUsersConfig),
        };
    } catch (error) {
        console.error('Invalid warehouse page config JSON:', error);
        return null;
    }
}

function registerWarehouseShowPage() {
    if (!window.Alpine) return;

    const config = getWarehousePageConfigs();
    if (!config) return;

    window.warehouseShowConfig = config.show;
    window.warehouseUsersTableConfig = config.users;

    Alpine.data('warehouseShow', warehouseShow);
    Alpine.data('warehouseUsersTable', (componentConfig = null) => {
        const resolvedConfig = componentConfig || window.warehouseUsersTableConfig || {};
        return warehouseUsersTable(resolvedConfig);
    });
}

if (window.Alpine) {
    registerWarehouseShowPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseShowPage);
}
