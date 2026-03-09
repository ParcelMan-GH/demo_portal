/**
 * Admin roles listing page Alpine component
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

function buildRolesTable(config) {
    return {
        endpoint: config.endpoint,
        exportEndpoint: config.exportEndpoint,
        createUrl: config.createUrl,
        csrfToken: config.csrfToken,
        scope: config.scope || 'system',
        canCreate: !!config.canCreate,
        roles: [],
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },
        loading: false,
        exporting: false,
        search: '',
        statusFilter: '',
        statusFilterName: 'All statuses',
        typeFilter: '',
        typeFilterName: 'All types',
        createdFrom: '',
        createdTo: '',
        dateRangePicker: null,
        perPage: 50,
        sortBy: 'created_at',
        sortDirection: 'desc',
        columns: [
            { key: 'name', label: 'Role Name' },
            { key: 'users_count', label: 'Users' },
            { key: 'permissions_count', label: 'Permissions' },
            { key: 'type_label', label: 'Type' },
            { key: 'status_label', label: 'Status' },
            { key: 'created_at', label: 'Created At' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            name: true,
            users_count: true,
            permissions_count: true,
            type_label: true,
            status_label: true,
            created_at: true,
            actions: true,
        },

        init() {
            this.initDateRange();
            this.loadData();
        },

        isWarehouseScope() {
            return this.scope === 'warehouse';
        },

        setStatusFilter(value, label) {
            this.statusFilter = value;
            this.statusFilterName = label;
            this.meta.current_page = 1;
            this.loadData();
        },

        setTypeFilter(value, label) {
            this.typeFilter = value;
            this.typeFilterName = label;
            this.meta.current_page = 1;
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
                    scope: this.scope,
                });

                if (this.search) params.append('search', this.search);
                if (this.statusFilter !== '' && this.statusFilter !== null) params.append('status', this.statusFilter);
                if (this.typeFilter) params.append('type', this.typeFilter);
                if (this.createdFrom) params.append('date_from', this.createdFrom);
                if (this.createdTo) params.append('date_to', this.createdTo);

                const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch roles');

                const result = await response.json();
                this.roles = result.data || [];
                this.meta = {
                    current_page: result.meta?.current_page ?? 1,
                    from: result.meta?.from ?? 0,
                    to: result.meta?.to ?? 0,
                    total: result.meta?.total ?? 0,
                    last_page: result.meta?.last_page ?? 1,
                };
            } catch (error) {
                console.error('Error loading roles:', error);
                if (window.showToast) {
                    window.showToast('Failed to load roles.', 'error');
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
            this.meta.current_page = 1;
            this.loadData();
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
                        Today: [window.moment(), window.moment()],
                        Yesterday: [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
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
                    this.meta.current_page = 1;
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

            ensureDateRangeDependencies()
                .then(() => {
                    window.$ = window.jQuery = window.$ || window.jQuery;
                    window.moment = window.moment || moment;
                    setupPicker();
                })
                .catch((error) => {
                    console.error('Failed to initialize date range picker:', error);
                });
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

        async deleteRole(role) {
            if (!role || !role.can_delete || !role.delete_url) return;
            if (!window.confirm(`Delete role "${role.name}"? This action cannot be undone.`)) return;

            try {
                const response = await fetch(role.delete_url, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result.message || 'Failed to delete role.');
                }

                if (window.showToast) {
                    window.showToast(result.message || 'Role deleted successfully.', 'success');
                }

                if (this.roles.length === 1 && this.meta.current_page > 1) {
                    this.meta.current_page--;
                }
                await this.loadData();
            } catch (error) {
                console.error('Delete role failed:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to delete role.', 'error');
                }
            }
        },

        async exportData(format) {
            this.exporting = true;
            try {
                const params = new URLSearchParams({
                    scope: this.scope,
                    format,
                });

                if (this.search) params.append('search', this.search);
                if (this.statusFilter !== '' && this.statusFilter !== null) params.append('status', this.statusFilter);
                if (this.typeFilter) params.append('type', this.typeFilter);
                if (this.createdFrom) params.append('date_from', this.createdFrom);
                if (this.createdTo) params.append('date_to', this.createdTo);

                if (format === 'excel' || format === 'pdf') {
                    window.location.href = `${this.exportEndpoint}?${params.toString()}`;
                    setTimeout(() => {
                        this.exporting = false;
                    }, 700);
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
                    this.downloadCSV(result.data || []);
                } else {
                    this.openPrintWindow(result.data || []);
                }
            } catch (error) {
                console.error('Export failed:', error);
                if (window.showToast) {
                    window.showToast('Export failed. Please try again.', 'error');
                }
            } finally {
                this.exporting = false;
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

            doc.title = 'Roles Export';
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
            title.textContent = 'Roles List';
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

            this.downloadFile(csvContent, 'roles.csv', 'text/csv');
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
    };
}

function getRolesConfig() {
    const container = document.querySelector('[data-roles-config]');
    let config = window.rolesTableConfig || null;

    if (container) {
        try {
            config = JSON.parse(container.getAttribute('data-roles-config'));
        } catch (error) {
            console.error('Invalid roles config JSON:', error);
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

function registerRolesTable() {
    if (!window.Alpine) {
        return;
    }

    const config = getRolesConfig();
    if (!config) {
        return;
    }

    if (!window.rolesTable) {
        window.rolesTable = () => buildRolesTable(config);
    }

    Alpine.data('rolesTable', window.rolesTable);
}

if (window.Alpine) {
    registerRolesTable();
} else {
    document.addEventListener('alpine:init', registerRolesTable);
}
