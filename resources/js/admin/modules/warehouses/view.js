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
        changePassword: false,
        userFormErrors: {},
        userForm: {
            name: '',
            email: '',
            phone: '',
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
        statusFilterName: 'All statuses',
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
            phone: true,
            status: true,
            created_at: true,
            last_login_at: true,
            actions: true,
        },
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

        openCreateModal() {
            this.userModalMode = 'create';
            this.selectedUser = null;
            this.userFormErrors = {};
            this.changePassword = false;
            this.userForm = {
                name: '',
                email: '',
                phone: '',
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
            this.changePassword = false;
            this.userForm = {
                name: user.name || '',
                email: user.email || '',
                phone: user.phone || '',
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
            this.changePassword = false;
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
                phone: this.userForm.phone,
                role_id: this.userForm.role_id,
                warehouse_id: this.warehouseId,
            };

            if (!isCreate && this.selectedUser && !this.selectedUser.is_self) {
                payload.is_active = this.userForm.is_active;
            }

            if (this.userForm.password && (isCreate || this.changePassword)) {
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
                const data = Array.isArray(result.data) ? result.data : [];
                if (format === 'print') {
                    this.openPrintWindow(data);
                    return;
                }
                this.downloadCSV(data);
            } catch (error) {
                console.error('Warehouse users export failed:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Export failed', 'error');
                }
            }
        },

        printData() {
            return this.exportData('print');
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
            const headers = ['Name', 'Role', 'Email', 'Phone', 'Status', 'Created At', 'Last Login'];

            if (!doc.documentElement) doc.appendChild(doc.createElement('html'));
            if (!doc.head) doc.documentElement.appendChild(doc.createElement('head'));
            if (!doc.body) doc.documentElement.appendChild(doc.createElement('body'));

            doc.title = 'Warehouse Users Export';
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
            title.textContent = 'Warehouse Users';
            doc.body.appendChild(title);

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
                const roleName = row.roles && row.roles.length ? row.roles[0].name : 'No role';
                const values = [
                    row.name ?? '-',
                    roleName,
                    row.email ?? '-',
                    row.phone ?? '-',
                    row.is_active ? 'Active' : 'Inactive',
                    row.created_at ?? '-',
                    row.last_login_at ?? '-',
                ];

                values.forEach((value) => {
                    const td = doc.createElement('td');
                    td.textContent = value;
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

// ─── Client-side inventory table factory ────────────────────────────────────
function clientSideTable({ items = [], columns = [], visibleColumns = {}, defaultSort = '', defaultSortDir = 'desc', title = 'Export', filename = 'export' }) {
    return {
        allItems: items,
        items: [],
        columns: columns,
        visibleColumns: { ...visibleColumns },
        search: '',
        sortBy: defaultSort,
        sortDir: defaultSortDir,
        perPage: 25,
        currentPage: 1,
        meta: { total: 0, last_page: 1, current_page: 1, from: 0, to: 0 },
        _title: title,
        _filename: filename,

        init() {
            this.recompute();
            this.$watch('search', () => { this.currentPage = 1; this.recompute(); });
            this.$watch('perPage', () => { this.currentPage = 1; this.recompute(); });
        },

        recompute() {
            let data = this.allItems;
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                data = data.filter(row =>
                    Object.values(row).some(v => String(v ?? '').toLowerCase().includes(q))
                );
            }
            if (this.sortBy) {
                data = [...data].sort((a, b) => {
                    const va = a[this.sortBy] ?? '';
                    const vb = b[this.sortBy] ?? '';
                    const cmp = String(va).localeCompare(String(vb), undefined, { numeric: true });
                    return this.sortDir === 'asc' ? cmp : -cmp;
                });
            }
            const total = data.length;
            const lastPage = Math.max(1, Math.ceil(total / this.perPage));
            if (this.currentPage > lastPage) this.currentPage = lastPage;
            const from = total === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
            const to = Math.min(this.currentPage * this.perPage, total);
            this.meta = { total, last_page: lastPage, current_page: this.currentPage, from, to };
            this.items = data.slice((this.currentPage - 1) * this.perPage, this.currentPage * this.perPage);
        },

        sort(col) {
            if (this.sortBy === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = col;
                this.sortDir = 'asc';
            }
            this.currentPage = 1;
            this.recompute();
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        setPerPage(n) { this.perPage = n; },
        firstPage()   { if (this.currentPage !== 1)                     { this.currentPage = 1;                   this.recompute(); } },
        prevPage()    { if (this.currentPage > 1)                        { this.currentPage--;                     this.recompute(); } },
        nextPage()    { if (this.currentPage < this.meta.last_page)      { this.currentPage++;                     this.recompute(); } },
        lastPage()    { if (this.currentPage !== this.meta.last_page)    { this.currentPage = this.meta.last_page; this.recompute(); } },

        exportData(format) {
            let data = this.allItems;
            if (this.search.trim()) {
                const q = this.search.toLowerCase();
                data = data.filter(row => Object.values(row).some(v => String(v ?? '').toLowerCase().includes(q)));
            }
            if (this.sortBy) {
                data = [...data].sort((a, b) => {
                    const cmp = String(a[this.sortBy] ?? '').localeCompare(String(b[this.sortBy] ?? ''), undefined, { numeric: true });
                    return this.sortDir === 'asc' ? cmp : -cmp;
                });
            }
            if (format === 'csv')   this.downloadCSV(data);
            if (format === 'print') this.openPrintWindow(data);
        },

        printData() { this.exportData('print'); },

        downloadCSV(data) {
            if (!data.length) { window.showToast?.('No data to export', 'warning'); return; }
            const exportCols = this.columns.filter(c => c.exportable !== false);
            const headers = exportCols.map(c => c.label);
            const keys    = exportCols.map(c => c.key);
            let csv = headers.join(',') + '\n';
            data.forEach(row => {
                csv += keys.map(k => `"${String(row[k] ?? '').replace(/"/g, '""')}"`).join(',') + '\n';
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url  = URL.createObjectURL(blob);
            const a    = Object.assign(document.createElement('a'), { href: url, download: this._filename + '.csv' });
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },

        openPrintWindow(data) {
            if (!data.length) { window.showToast?.('No data to print.', 'warning'); return; }
            const win = window.open('', '_blank');
            if (!win) { window.showToast?.('Pop-up blocked. Allow pop-ups to print.', 'warning'); return; }
            const exportCols = this.columns.filter(c => c.exportable !== false);
            const headers    = exportCols.map(c => c.label);
            const keys       = exportCols.map(c => c.key);
            const rows = data.map(row =>
                `<tr>${keys.map(k => `<td>${String(row[k] ?? '-').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>`).join('')}</tr>`
            ).join('');
            win.document.write(
                `<!DOCTYPE html><html><head><title>${this._title}</title>` +
                `<style>body{font-family:sans-serif;padding:20px}h1{font-size:20px;margin-bottom:16px;color:#1e293b}` +
                `table{width:100%;border-collapse:collapse}th,td{border:1px solid #e2e8f0;padding:8px 12px;text-align:left;font-size:12px}` +
                `th{background:#f1f5f9;font-weight:600;color:#475569}tr:nth-child(even){background:#f8fafc}</style></head>` +
                `<body><h1>${this._title}</h1><table><thead><tr>` +
                `${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>${rows}</tbody></table></body></html>`
            );
            win.document.close();
            setTimeout(() => win.print(), 250);
        },
    };
}

function warehouseReceivedItemsTable(items) {
    return clientSideTable({
        items: Array.isArray(items) ? items : [],
        columns: [
            { key: 'confirmed_at',    label: 'Confirmed At' },
            { key: 'shipment_number', label: 'Shipment' },
            { key: 'item_description',label: 'Item', exportable: true },
            { key: 'qty',             label: 'Qty' },
            { key: 'driver',          label: 'Driver' },
            { key: 'notes',           label: 'Notes', exportable: true },
            { key: 'actions',         label: 'Actions', exportable: false },
        ],
        visibleColumns: { confirmed_at: true, shipment_number: true, item_description: true, qty: true, driver: true, notes: true, actions: true },
        defaultSort: 'confirmed_at',
        defaultSortDir: 'desc',
        title: 'Received Items',
        filename: 'received-items',
    });
}

function warehouseReceivedPickupsTable(items) {
    return clientSideTable({
        items: Array.isArray(items) ? items : [],
        columns: [
            { key: 'shipment_number',     label: 'Shipment' },
            { key: 'driver',              label: 'Driver' },
            { key: 'status_label',        label: 'Status' },
            { key: 'arrived_warehouse_at',label: 'Arrived Warehouse' },
            { key: 'received_at',         label: 'Received At' },
            { key: 'notes',               label: 'Notes', exportable: true },
            { key: 'actions',             label: 'Actions', exportable: false },
        ],
        visibleColumns: { shipment_number: true, driver: true, status_label: true, arrived_warehouse_at: true, received_at: true, notes: true, actions: true },
        defaultSort: 'received_at',
        defaultSortDir: 'desc',
        title: 'Received Pickups',
        filename: 'received-pickups',
    });
}

function warehousePendingReceiptsTable(items) {
    return clientSideTable({
        items: Array.isArray(items) ? items : [],
        columns: [
            { key: 'shipment_number',     label: 'Shipment' },
            { key: 'driver',              label: 'Driver' },
            { key: 'status_label',        label: 'Status' },
            { key: 'assigned_at',         label: 'Assigned At' },
            { key: 'arrived_warehouse_at',label: 'Arrived Warehouse' },
            { key: 'actions',             label: 'Actions', exportable: false },
        ],
        visibleColumns: { shipment_number: true, driver: true, status_label: true, assigned_at: true, arrived_warehouse_at: true, actions: true },
        defaultSort: 'assigned_at',
        defaultSortDir: 'desc',
        title: 'Pending Receipts',
        filename: 'pending-receipts',
    });
}

function warehouseSortBatchesTable(items) {
    return clientSideTable({
        items: Array.isArray(items) ? items : [],
        columns: [
            { key: 'batch_number',   label: 'Batch #' },
            { key: 'direction',      label: 'Direction' },
            { key: 'other_warehouse',label: 'Other Warehouse' },
            { key: 'dispatch_mode',  label: 'Mode' },
            { key: 'status',         label: 'Status' },
            { key: 'items',          label: 'Items' },
            { key: 'sealed_at',      label: 'Sealed At' },
            { key: 'created_at',     label: 'Created' },
            { key: 'actions',        label: 'Actions', exportable: false },
        ],
        visibleColumns: { batch_number: true, direction: true, other_warehouse: true, dispatch_mode: true, status: true, items: true, sealed_at: true, created_at: true, actions: true },
        defaultSort: 'created_at',
        defaultSortDir: 'desc',
        title: 'Sort Batches',
        filename: 'sort-batches',
    });
}

function warehouseManifestsTable(items) {
    return clientSideTable({
        items: Array.isArray(items) ? items : [],
        columns: [
            { key: 'manifest_number', label: 'Manifest #' },
            { key: 'direction',       label: 'Direction' },
            { key: 'other_warehouse', label: 'Other Warehouse' },
            { key: 'driver',          label: 'Driver' },
            { key: 'status',          label: 'Status' },
            { key: 'items',           label: 'Items' },
            { key: 'dispatched_at',   label: 'Dispatched' },
            { key: 'arrived_at',      label: 'Arrived' },
            { key: 'created_at',      label: 'Created' },
            { key: 'actions',         label: 'Actions', exportable: false },
        ],
        visibleColumns: { manifest_number: true, direction: true, other_warehouse: true, driver: true, status: true, items: true, dispatched_at: true, arrived_at: true, created_at: true, actions: true },
        defaultSort: 'created_at',
        defaultSortDir: 'desc',
        title: 'Transport Manifests',
        filename: 'transport-manifests',
    });
}

function warehouseDeliveryRunsTable(items) {
    return clientSideTable({
        items: Array.isArray(items) ? items : [],
        columns: [
            { key: 'run_number',    label: 'Run #' },
            { key: 'driver',        label: 'Driver' },
            { key: 'status',        label: 'Status' },
            { key: 'stops',         label: 'Stops' },
            { key: 'dispatched_at', label: 'Dispatched' },
            { key: 'completed_at',  label: 'Completed' },
            { key: 'created_at',    label: 'Created' },
            { key: 'actions',       label: 'Actions', exportable: false },
        ],
        visibleColumns: { run_number: true, driver: true, status: true, stops: true, dispatched_at: true, completed_at: true, created_at: true, actions: true },
        defaultSort: 'created_at',
        defaultSortDir: 'desc',
        title: 'Delivery Runs',
        filename: 'delivery-runs',
    });
}

function warehouseShow() {
    return {
        config: {},
        warehouse: {},
        canManage: false,
        activeTab: 'details',
        capabilitiesLoaded: false,
        loadingCapabilities: false,
        savingCapabilities: false,
        capabilityModules: [],
        capabilityForm: {},

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
            this.prepareCapabilityForm();
        },

        tabClass(tab) {
            return this.activeTab === tab
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50';
        },

        prepareCapabilityForm(modules = null, capabilities = {}) {
            const sourceModules = modules || {};
            this.capabilityModules = Object.entries(sourceModules).map(([key, label]) => ({ key, label }));

            this.capabilityForm = {};
            this.capabilityModules.forEach((module) => {
                const capability = capabilities[module.key] || null;
                this.capabilityForm[module.key] = capability ? {
                    enabled: true,
                    scope: capability.scope || 'own',
                    allowed_warehouse_ids: (capability.allowed_warehouse_ids || []).map((id) => String(id)),
                } : {
                    enabled: false,
                    scope: 'own',
                    allowed_warehouse_ids: [],
                };
            });
        },

        async loadCapabilities() {
            if (!this.config.canManageCapabilities || this.config.isHqWarehouse || this.capabilitiesLoaded || this.loadingCapabilities) {
                return;
            }

            this.loadingCapabilities = true;

            try {
                const response = await fetch(this.config.capabilitiesEndpoint, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to load capabilities');
                }

                this.prepareCapabilityForm(data.modules || {}, data.capabilities || {});
                this.capabilitiesLoaded = true;
            } catch (error) {
                console.error('Capabilities load error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to load capabilities', 'error');
                }
            } finally {
                this.loadingCapabilities = false;
            }
        },

        isCapabilityEnabled(module) {
            return Boolean(this.capabilityForm[module]?.enabled);
        },

        toggleCapability(module, enabled) {
            if (!this.capabilityForm[module]) {
                this.capabilityForm[module] = { enabled: false, scope: 'own', allowed_warehouse_ids: [] };
            }

            this.capabilityForm[module].enabled = enabled;

            if (!enabled) {
                this.capabilityForm[module].scope = 'own';
                this.capabilityForm[module].allowed_warehouse_ids = [];
            }
        },

        enabledCapabilitiesCount() {
            return Object.values(this.capabilityForm || {}).filter((item) => item.enabled).length;
        },

        capabilityDescription(module) {
            const descriptions = {
                dashboard: 'Dashboard access for this warehouse.',
                warehouse: 'Local warehouse operations such as receiving, sorting, and manifests.',
                warehouses: 'Warehouse listing and cross-warehouse management.',
                vendors: 'Vendor listing, vendor details, and vendor payout workflows.',
                shipments: 'Shipment management and assignment workflows.',
                drivers: 'Rider and driver management.',
                reports: 'Operational and finance reports.',
                recipient_payments: 'Recipient payment recording and reporting.',
                invoices: 'Invoice access and controls.',
                users: 'Local user management for this warehouse.',
                roles: 'Role visibility. HQ still owns role definitions in this phase.',
                settings: 'System settings access.',
                marketing: 'Marketing and communication controls.',
            };

            return descriptions[module] || 'Back-office module access.';
        },

        async saveCapabilities() {
            if (this.config.isHqWarehouse) {
                return;
            }

            this.savingCapabilities = true;

            const capabilities = Object.entries(this.capabilityForm)
                .filter(([, capability]) => capability.enabled)
                .map(([module, capability]) => ({
                    module,
                    scope: capability.scope || 'own',
                    allowed_warehouse_ids: capability.scope === 'selected'
                        ? (capability.allowed_warehouse_ids || []).map((id) => Number(id)).filter(Boolean)
                        : [],
                }));

            try {
                const response = await fetch(this.config.capabilitiesUpdateEndpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ capabilities }),
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to save capabilities');
                }

                this.prepareCapabilityForm(data.modules || {}, data.capabilities || {});
                this.capabilitiesLoaded = true;

                if (window.showToast) {
                    window.showToast('Warehouse capabilities saved.', 'success');
                }
            } catch (error) {
                console.error('Capabilities save error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to save capabilities', 'error');
                }
            } finally {
                this.savingCapabilities = false;
            }
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
    Alpine.data('warehouseReceivedItemsTable',  (items = []) => warehouseReceivedItemsTable(items));
    Alpine.data('warehouseReceivedPickupsTable', (items = []) => warehouseReceivedPickupsTable(items));
    Alpine.data('warehousePendingReceiptsTable', (items = []) => warehousePendingReceiptsTable(items));
    Alpine.data('warehouseSortBatchesTable',     (items = []) => warehouseSortBatchesTable(items));
    Alpine.data('warehouseManifestsTable',       (items = []) => warehouseManifestsTable(items));
    Alpine.data('warehouseDeliveryRunsTable',    (items = []) => warehouseDeliveryRunsTable(items));
}

if (window.Alpine) {
    registerWarehouseShowPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseShowPage);
}
