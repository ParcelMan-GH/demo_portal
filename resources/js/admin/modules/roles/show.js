/**
 * Admin role details page Alpine component
 */

function getRoleShowConfig() {
    const container = document.querySelector('[data-role-show-config]');
    let config = null;

    if (container) {
        try {
            config = JSON.parse(container.getAttribute('data-role-show-config') || '{}');
        } catch (error) {
            console.error('Invalid role show config JSON:', error);
        }
    }

    return config || {
        initialTab: 'permissions',
        users: [],
    };
}

function normalizeText(value) {
    return (value || '').toString().trim().toLowerCase();
}

function toCsvValue(value) {
    const safe = (value ?? '').toString().replace(/"/g, '""');
    return `"${safe}"`;
}

function buildRoleShowPage(config) {
    return {
        activeTab: ['permissions', 'users'].includes(config.initialTab) ? config.initialTab : 'permissions',
        sourceUsers: Array.isArray(config.users) ? config.users : [],
        filteredRows: [],
        users: [],
        search: '',
        showFilters: false,
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
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'role', label: 'Role' },
            { key: 'email', label: 'Email' },
            { key: 'warehouse', label: 'Warehouse' },
            { key: 'created_at', label: 'Created At' },
            { key: 'last_login_at', label: 'Last Login' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            name: true,
            role: true,
            email: true,
            warehouse: true,
            created_at: true,
            last_login_at: true,
            actions: true,
        },

        init() {
            this.applyFilters();
        },

        setTab(tab) {
            if (!['permissions', 'users'].includes(tab)) return;
            this.activeTab = tab;

            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url.toString());
        },

        setStatusFilter(value, label) {
            this.statusFilter = value;
            this.statusFilterName = label;
            this.meta.current_page = 1;
            this.applyFilters();
        },

        clearFilters() {
            this.search = '';
            this.statusFilter = '';
            this.statusFilterName = 'All statuses';
            this.meta.current_page = 1;
            this.applyFilters();
        },

        setPerPage(value) {
            const parsed = Number(value);
            if (!Number.isFinite(parsed) || parsed <= 0) return;
            this.perPage = parsed;
            this.meta.current_page = 1;
            this.applyFilters();
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDirection = 'asc';
            }
            this.meta.current_page = 1;
            this.applyFilters();
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        applyFilters() {
            let rows = [...this.sourceUsers];
            const term = normalizeText(this.search);

            if (term) {
                rows = rows.filter((user) => {
                    const haystack = [
                        user.name,
                        user.email,
                        user.warehouse_name,
                        user.role_name,
                        user.status_label,
                    ].map(normalizeText).join(' ');
                    return haystack.includes(term);
                });
            }

            if (this.statusFilter === '1') {
                rows = rows.filter((user) => !!user.is_active);
            } else if (this.statusFilter === '0') {
                rows = rows.filter((user) => !user.is_active);
            }

            rows.sort((a, b) => {
                let valueA;
                let valueB;

                if (this.sortBy === 'created_at') {
                    valueA = a.created_at_raw || '';
                    valueB = b.created_at_raw || '';
                } else if (this.sortBy === 'last_login_at') {
                    valueA = a.last_login_at_raw || '';
                    valueB = b.last_login_at_raw || '';
                } else if (this.sortBy === 'name') {
                    valueA = normalizeText(a.name);
                    valueB = normalizeText(b.name);
                } else if (this.sortBy === 'email') {
                    valueA = normalizeText(a.email);
                    valueB = normalizeText(b.email);
                } else if (this.sortBy === 'warehouse_name') {
                    valueA = normalizeText(a.warehouse_name);
                    valueB = normalizeText(b.warehouse_name);
                } else {
                    valueA = normalizeText(a[this.sortBy]);
                    valueB = normalizeText(b[this.sortBy]);
                }

                if (valueA === valueB) return 0;
                const compare = valueA > valueB ? 1 : -1;
                return this.sortDirection === 'asc' ? compare : -compare;
            });

            this.filteredRows = rows;

            this.meta.total = rows.length;
            this.meta.last_page = Math.max(1, Math.ceil(rows.length / this.perPage));

            if (this.meta.current_page > this.meta.last_page) {
                this.meta.current_page = this.meta.last_page;
            }

            const startIndex = (this.meta.current_page - 1) * this.perPage;
            const endIndex = startIndex + this.perPage;
            this.users = rows.slice(startIndex, endIndex);

            if (!rows.length) {
                this.meta.from = 0;
                this.meta.to = 0;
            } else {
                this.meta.from = startIndex + 1;
                this.meta.to = startIndex + this.users.length;
            }
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) {
                this.meta.current_page += 1;
                this.applyFilters();
            }
        },

        previousPage() {
            if (this.meta.current_page > 1) {
                this.meta.current_page -= 1;
                this.applyFilters();
            }
        },

        firstPage() {
            if (this.meta.current_page !== 1) {
                this.meta.current_page = 1;
                this.applyFilters();
            }
        },

        lastPage() {
            if (this.meta.current_page !== this.meta.last_page) {
                this.meta.current_page = this.meta.last_page;
                this.applyFilters();
            }
        },

        exportData(format) {
            if (!this.filteredRows.length) {
                if (window.showToast) {
                    window.showToast('No data to export.', 'warning');
                }
                return;
            }

            if (format === 'csv') {
                this.downloadCsv(this.filteredRows, 'role-assigned-users.csv');
                return;
            }

            if (format === 'excel') {
                this.downloadCsv(this.filteredRows, 'role-assigned-users.xls');
                return;
            }

            if (format === 'pdf') {
                this.openPrintWindow(this.filteredRows, true);
                return;
            }

            this.printData();
        },

        printData() {
            if (!this.filteredRows.length) {
                if (window.showToast) {
                    window.showToast('No data to print.', 'warning');
                }
                return;
            }
            this.openPrintWindow(this.filteredRows, false);
        },

        downloadCsv(rows, filename) {
            const headers = ['Name', 'Role', 'Email', 'Warehouse', 'Status', 'Created At', 'Last Login'];
            let csvContent = `${headers.map(toCsvValue).join(',')}\n`;

            rows.forEach((row) => {
                csvContent += [
                    toCsvValue(row.name),
                    toCsvValue(row.role_name),
                    toCsvValue(row.email),
                    toCsvValue(row.warehouse_name),
                    toCsvValue(row.status_label),
                    toCsvValue(row.created_at),
                    toCsvValue(row.last_login_at),
                ].join(',');
                csvContent += '\n';
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        openPrintWindow(rows, pdfMode = false) {
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                if (window.showToast) {
                    window.showToast('Pop-up blocked. Please allow pop-ups to continue.', 'warning');
                }
                return;
            }

            const renderedRows = rows.map((row) => `
                <tr>
                    <td>${row.name || '-'}</td>
                    <td>${row.role_name || '-'}</td>
                    <td>${row.email || '-'}</td>
                    <td>${row.warehouse_name || '-'}</td>
                    <td>${row.status_label || '-'}</td>
                    <td>${row.created_at || '-'}</td>
                    <td>${row.last_login_at || '-'}</td>
                </tr>
            `).join('');

            printWindow.document.write(`
                <html>
                    <head>
                        <title>Assigned Users Export</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 24px; color: #0f172a; }
                            h1 { margin-bottom: 12px; }
                            table { width: 100%; border-collapse: collapse; }
                            th, td { border: 1px solid #cbd5e1; padding: 8px; font-size: 12px; text-align: left; }
                            th { background: #f8fafc; font-weight: 700; }
                        </style>
                    </head>
                    <body>
                        <h1>Assigned Users</h1>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Warehouse</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody>${renderedRows}</tbody>
                        </table>
                    </body>
                </html>
            `);
            printWindow.document.close();

            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                if (pdfMode && window.showToast) {
                    window.showToast('Use the print dialog to save as PDF.', 'info');
                }
            }, 250);
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('roleShowPage', () => buildRoleShowPage(getRoleShowConfig()));
});
