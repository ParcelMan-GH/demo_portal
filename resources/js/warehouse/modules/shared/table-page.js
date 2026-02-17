function toCsvValue(value) {
    const safe = (value ?? '').toString().replace(/"/g, '""');
    return `"${safe}"`;
}

function normalizeText(value) {
    return (value ?? '').toString().trim().toLowerCase();
}

export function createRemoteTablePage(config) {
    const columns = Array.isArray(config.columns) ? config.columns : [];
    const defaultSort = config.defaultSort || (columns.find((col) => col.sortable !== false)?.key ?? 'created_at');

    return {
        endpoint: config.endpoint,
        search: '',
        statusFilter: '',
        statusFilterName: 'All statuses',
        statuses: Array.isArray(config.statuses) ? config.statuses : [],
        rows: [],
        loading: false,
        perPage: Number(config.defaultPerPage || 10),
        sortBy: defaultSort,
        sortDirection: 'desc',
        columns,
        visibleColumns: columns.reduce((acc, col) => {
            acc[col.key] = col.visible !== false;
            return acc;
        }, {}),
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },

        init() {
            this.loadData();
        },

        isSortable(key) {
            const column = this.columns.find((item) => item.key === key);
            return Boolean(column && column.sortable !== false);
        },

        sort(column) {
            if (!this.isSortable(column)) return;

            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDirection = 'asc';
            }

            this.meta.current_page = 1;
            this.loadData();
        },

        setStatusFilter(value, label = 'All statuses') {
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

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        firstPage() {
            if (this.meta.current_page > 1) {
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
            if (this.meta.current_page < this.meta.last_page) {
                this.meta.current_page = this.meta.last_page;
                this.loadData();
            }
        },

        buildParams(overrides = {}) {
            const page = overrides.page ?? this.meta.current_page;
            const perPage = overrides.per_page ?? this.perPage;

            const params = new URLSearchParams({
                page: String(page),
                per_page: String(perPage),
                sort: overrides.sort ?? this.sortBy,
                direction: overrides.direction ?? this.sortDirection,
            });

            const search = overrides.search ?? this.search;
            const status = overrides.status ?? this.statusFilter;

            if (search) params.append('search', search);
            if (status !== '' && status !== null && status !== undefined) params.append('status', status);

            return params;
        },

        async loadData() {
            this.loading = true;

            try {
                const response = await fetch(`${this.endpoint}?${this.buildParams().toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch records.');
                }

                const result = await response.json();

                this.rows = Array.isArray(result.data) ? result.data : [];
                this.meta = result.meta || this.meta;
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to load records.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async fetchExportRows(limit = 500) {
            const params = this.buildParams({ page: 1, per_page: limit });
            const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch export data.');
            }

            const result = await response.json();
            return Array.isArray(result.data) ? result.data : [];
        },

        async exportData(format) {
            try {
                const rows = await this.fetchExportRows();

                if (!rows.length) {
                    window.showToast?.('No data available to export.', 'warning');
                    return;
                }

                const fileName = config.exportFileName || 'warehouse-report';

                if (format === 'csv') {
                    this.downloadCSV(rows, `${fileName}.csv`);
                    return;
                }

                if (format === 'print' || format === 'pdf') {
                    this.openPrintWindow(rows, format === 'pdf', config.printTitle || 'Warehouse Report');
                }
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Export failed.', 'error');
            }
        },

        downloadCSV(rows, filename) {
            if (!rows.length) return;

            const exportColumns = this.columns.filter((col) => col.key !== 'actions');
            const headers = exportColumns.map((col) => col.exportLabel || col.label || col.key);

            const csvLines = [headers.map(toCsvValue).join(',')];
            rows.forEach((row) => {
                const line = exportColumns.map((col) => toCsvValue(row[col.key]));
                csvLines.push(line.join(','));
            });

            const blob = new Blob([csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        openPrintWindow(rows, pdfMode = false, title = 'Warehouse Report') {
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                window.showToast?.('Pop-up blocked. Please allow pop-ups.', 'warning');
                return;
            }

            const exportColumns = this.columns.filter((col) => col.key !== 'actions');
            const headersHtml = exportColumns.map((col) => `<th>${col.exportLabel || col.label || col.key}</th>`).join('');
            const rowsHtml = rows.map((row) => {
                const cells = exportColumns.map((col) => `<td>${row[col.key] ?? '-'}</td>`).join('');
                return `<tr>${cells}</tr>`;
            }).join('');

            printWindow.document.write(`
                <html>
                    <head>
                        <title>${title}</title>
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; color: #0f172a; }
                            h1 { margin-bottom: 16px; }
                            table { width: 100%; border-collapse: collapse; }
                            th, td { border: 1px solid #cbd5e1; padding: 8px; font-size: 12px; text-align: left; }
                            th { background: #f8fafc; font-weight: 700; }
                        </style>
                    </head>
                    <body>
                        <h1>${title}</h1>
                        <table>
                            <thead><tr>${headersHtml}</tr></thead>
                            <tbody>${rowsHtml}</tbody>
                        </table>
                    </body>
                </html>
            `);

            printWindow.document.close();

            window.setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                if (pdfMode) {
                    window.showToast?.('Use the print dialog to save as PDF.', 'info');
                }
            }, 250);
        },

        applyLocalSearch(rows, searchKeys = []) {
            const term = normalizeText(this.search);
            if (!term) return rows;

            return rows.filter((row) => searchKeys.some((key) => normalizeText(row[key]).includes(term)));
        },
    };
}
