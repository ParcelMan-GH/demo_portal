/**
 * Audit logs datatable Alpine component for the admin user show page.
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
        .then(() => loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js'));
}

document.addEventListener('alpine:init', () => {
    Alpine.data('auditLogsTable', () => ({
        endpoint: '',
        exportEndpoint: '',
        logs: [],
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
        },
        loading: false,
        search: '',
        dateFrom: '',
        dateTo: '',
        dateRangePicker: null,
        perPage: 15,
        sortBy: 'created_at',
        sortDirection: 'desc',
        columns: [
            { key: 'created_at', label: 'Date' },
            { key: 'type', label: 'Type' },
            { key: 'action', label: 'Action' },
            { key: 'request', label: 'Request' },
            { key: 'result', label: 'Result' },
        ],
        visibleColumns: {
            created_at: true,
            type: true,
            action: true,
            request: true,
            result: true,
        },

        init() {
            const dataset = this.$el?.dataset || {};
            this.endpoint = dataset.endpoint || '';
            this.exportEndpoint = dataset.exportEndpoint || '';

            if (!this.endpoint) {
                console.error('Audit logs table endpoint missing.');
                return;
            }

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
                if (this.dateFrom) params.append('date_from', this.dateFrom);
                if (this.dateTo) params.append('date_to', this.dateTo);

                const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch data');

                const result = await response.json();
                this.logs = result.data;
                this.meta = {
                    current_page: result.meta.current_page,
                    from: result.meta.from,
                    to: result.meta.to,
                    total: result.meta.total,
                    last_page: result.meta.last_page,
                };
            } catch (error) {
                console.error('Error loading audit logs:', error);
                if (window.showToast) {
                    window.showToast('Failed to load audit logs.', 'error');
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
            if (!this.$refs.dateRange) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                const $input = window.$(this.$refs.dateRange);

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
                    this.dateFrom = picker.startDate.format('YYYY-MM-DD');
                    this.dateTo = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${this.dateFrom} - ${this.dateTo}`);
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
            this.dateFrom = '';
            this.dateTo = '';
            if (this.dateRangePicker) {
                this.dateRangePicker.setStartDate(window.moment());
                this.dateRangePicker.setEndDate(window.moment());
            }
            if (this.$refs.dateRange) {
                this.$refs.dateRange.value = '';
            }
            this.loadData();
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) {
                this.meta.current_page++;
                this.loadData();
            }
        },

        previousPage() {
            if (this.meta.current_page > 1) {
                this.meta.current_page--;
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

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.dateFrom) params.append('date_from', this.dateFrom);
                if (this.dateTo) params.append('date_to', this.dateTo);
                params.append('format', format);

                if (format === 'excel' || format === 'pdf') {
                    window.location.href = `${this.exportEndpoint}?${params.toString()}`;
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
            }
        },

        async printData() {
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.dateFrom) params.append('date_from', this.dateFrom);
                if (this.dateTo) params.append('date_to', this.dateTo);

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
                if (window.showToast) window.showToast('No data to print.', 'warning');
                return;
            }

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                if (window.showToast) window.showToast('Pop-up blocked. Please allow pop-ups to print.', 'warning');
                return;
            }

            const doc = printWindow.document;
            const headers = Object.keys(data[0]);

            if (!doc.documentElement) doc.appendChild(doc.createElement('html'));
            if (!doc.head) doc.documentElement.appendChild(doc.createElement('head'));
            if (!doc.body) doc.documentElement.appendChild(doc.createElement('body'));
            doc.title = 'Activity Logs Export';
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
            title.textContent = 'Activity Logs';
            doc.body.appendChild(title);

            const meta = doc.createElement('p');
            meta.style.color = '#64748b';
            meta.style.fontSize = '14px';
            meta.style.marginBottom = '20px';
            meta.textContent = `Generated on ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`;
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
            let csvContent = `${headers.join(',')}\n`;

            data.forEach((row) => {
                const rowData = headers.map((header) => {
                    let cell = row[header] ?? '';
                    cell = String(cell).replace(/"/g, '""');
                    return `"${cell}"`;
                });
                csvContent += `${rowData.join(',')}\n`;
            });

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'activity-logs.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },
    }));
});
