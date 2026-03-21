/**
 * Admin vendor show page Alpine component
 * Extracted from Blade inline scripts and bundled via Vite.
 */

function vendorShow() {
    return {
        config: {},
        vendor: {},
        canManage: false,
        statuses: [],

        activeTab: 'shipments',
        showToggleModal: false,
        showDeleteModal: false,
        deleting: false,
        showRestoreModal: false,
        restoring: false,

        // Shipments state
        shipments: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            search: '',
            status: '',
            page: 1,
            perPage: 10,
            sortBy: 'created_at',
            sortDirection: 'desc',
            columns: [
                { key: 'shipment_number', label: 'Shipment #' },
                { key: 'recipient', label: 'Recipient' },
                { key: 'location', label: 'Location' },
                { key: 'items', label: 'Items' },
                { key: 'status', label: 'Status' },
                { key: 'created_at', label: 'Created' },
            ],
            visibleColumns: {
                shipment_number: true,
                recipient: true,
                location: true,
                items: true,
                status: true,
                created_at: true,
            },
        },

        // Activity logs state
        activity: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            search: '',
            action: '',
            page: 1,
            perPage: 10,
            sortBy: 'created_at',
            sortDirection: 'desc',
            columns: [
                { key: 'action', label: 'Action' },
                { key: 'description', label: 'Description' },
                { key: 'device', label: 'Device' },
                { key: 'ip_address', label: 'IP Address' },
                { key: 'created_at', label: 'Date' },
            ],
            visibleColumns: {
                action: true,
                description: true,
                device: true,
                ip_address: true,
                created_at: true,
            },
        },

        // OTP logs state
        otp: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            purpose: '',
            page: 1,
            perPage: 10,
            sortBy: 'created_at',
            sortDirection: 'desc',
            columns: [
                { key: 'code', label: 'Code' },
                { key: 'purpose', label: 'Purpose' },
                { key: 'status', label: 'Status' },
                { key: 'expires_at', label: 'Expires At' },
                { key: 'verified_at', label: 'Verified At' },
                { key: 'created_at', label: 'Created' },
            ],
            visibleColumns: {
                code: true,
                purpose: true,
                status: true,
                expires_at: true,
                verified_at: true,
                created_at: true,
            },
        },

        // Edit modal state
        showEditModal: false,
        saving: false,
        toggling: false,
        errors: {},
        form: {
            name: '',
            business_name: '',
            email: '',
            phone: '',
            is_active: true
        },

        init() {
            this.config = window.vendorShowConfig;
            this.vendor = this.config.vendor;
            this.canManage = this.config.canManage;
            this.statuses = this.config.statuses;
            this.loadShipments();
        },

        // ── Sort helpers ──────────────────────────────────────────
        sortShipments(column) {
            if (this.shipments.sortBy === column) {
                this.shipments.sortDirection = this.shipments.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.shipments.sortBy = column;
                this.shipments.sortDirection = 'asc';
            }
            this.shipments.page = 1;
            this.loadShipments();
        },

        sortActivity(column) {
            if (this.activity.sortBy === column) {
                this.activity.sortDirection = this.activity.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.activity.sortBy = column;
                this.activity.sortDirection = 'asc';
            }
            this.activity.page = 1;
            this.loadActivityLogs();
        },

        sortOtp(column) {
            if (this.otp.sortBy === column) {
                this.otp.sortDirection = this.otp.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.otp.sortBy = column;
                this.otp.sortDirection = 'asc';
            }
            this.otp.page = 1;
            this.loadOtpLogs();
        },

        // ── Column visibility helpers ──────────────────────────────
        toggleShipmentColumn(key) { this.shipments.visibleColumns[key] = !this.shipments.visibleColumns[key]; },
        toggleActivityColumn(key) { this.activity.visibleColumns[key] = !this.activity.visibleColumns[key]; },
        toggleOtpColumn(key) { this.otp.visibleColumns[key] = !this.otp.visibleColumns[key]; },

        visibleColumnCount(tab) {
            return Object.values(this[tab].visibleColumns).filter(Boolean).length;
        },

        // ── Pagination helpers ──────────────────────────────────────
        shipmentsFirstPage()    { this.shipments.page = 1; this.loadShipments(); },
        shipmentsPrevPage()     { if (this.shipments.page > 1) { this.shipments.page--; this.loadShipments(); } },
        shipmentsNextPage()     { if (this.shipments.page < this.shipments.meta.last_page) { this.shipments.page++; this.loadShipments(); } },
        shipmentsLastPage()     { this.shipments.page = this.shipments.meta.last_page; this.loadShipments(); },
        setShipmentsPerPage(n)  { this.shipments.perPage = n; this.shipments.page = 1; this.loadShipments(); },

        activityFirstPage()     { this.activity.page = 1; this.loadActivityLogs(); },
        activityPrevPage()      { if (this.activity.page > 1) { this.activity.page--; this.loadActivityLogs(); } },
        activityNextPage()      { if (this.activity.page < this.activity.meta.last_page) { this.activity.page++; this.loadActivityLogs(); } },
        activityLastPage()      { this.activity.page = this.activity.meta.last_page; this.loadActivityLogs(); },
        setActivityPerPage(n)   { this.activity.perPage = n; this.activity.page = 1; this.loadActivityLogs(); },

        otpFirstPage()          { this.otp.page = 1; this.loadOtpLogs(); },
        otpPrevPage()           { if (this.otp.page > 1) { this.otp.page--; this.loadOtpLogs(); } },
        otpNextPage()           { if (this.otp.page < this.otp.meta.last_page) { this.otp.page++; this.loadOtpLogs(); } },
        otpLastPage()           { this.otp.page = this.otp.meta.last_page; this.loadOtpLogs(); },
        setOtpPerPage(n)        { this.otp.perPage = n; this.otp.page = 1; this.loadOtpLogs(); },

        // ── Data loaders ──────────────────────────────────────────
        async loadShipments() {
            this.shipments.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.shipments.page,
                    per_page: this.shipments.perPage,
                    search: this.shipments.search,
                    status: this.shipments.status,
                    sort: this.shipments.sortBy,
                    direction: this.shipments.sortDirection,
                });

                const response = await fetch(`${this.config.shipmentsEndpoint}?${params}`);
                const data = await response.json();

                this.shipments.data = data.data;
                this.shipments.meta = data.meta;
            } catch (error) {
                console.error('Failed to load shipments:', error);
            } finally {
                this.shipments.loading = false;
            }
        },

        async loadActivityLogs() {
            this.activity.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.activity.page,
                    per_page: this.activity.perPage,
                    search: this.activity.search,
                    action: this.activity.action,
                    sort: this.activity.sortBy,
                    direction: this.activity.sortDirection,
                });

                const response = await fetch(`${this.config.activityLogsEndpoint}?${params}`);
                const data = await response.json();

                this.activity.data = data.data;
                this.activity.meta = data.meta;
            } catch (error) {
                console.error('Failed to load activity logs:', error);
            } finally {
                this.activity.loading = false;
            }
        },

        async loadOtpLogs() {
            this.otp.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.otp.page,
                    per_page: this.otp.perPage,
                    purpose: this.otp.purpose,
                    sort: this.otp.sortBy,
                    direction: this.otp.sortDirection,
                });

                const response = await fetch(`${this.config.otpLogsEndpoint}?${params}`);
                const data = await response.json();

                this.otp.data = data.data;
                this.otp.meta = data.meta;
            } catch (error) {
                console.error('Failed to load OTP logs:', error);
            } finally {
                this.otp.loading = false;
            }
        },

        // ── Print helpers ──────────────────────────────────────────
        printTable(tab) {
            const tabData = this[tab];
            if (!tabData.data.length) return;

            const printWindow = window.open('', '_blank');
            if (!printWindow) return;
            const doc = printWindow.document;

            const titles = { shipments: 'Shipments', activity: 'Activity Logs', otp: 'OTP Logs' };

            doc.title = titles[tab] + ' Export';
            doc.body.innerHTML = '';

            const style = doc.createElement('style');
            style.textContent = [
                'body { font-family: "Plus Jakarta Sans", -apple-system, sans-serif; padding: 20px; }',
                'h1 { font-size: 24px; margin-bottom: 20px; color: #1e293b; }',
                'table { width: 100%; border-collapse: collapse; margin-top: 20px; }',
                'th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; font-size: 12px; }',
                'th { background-color: #f1f5f9; font-weight: 600; color: #475569; }',
                'tr:nth-child(even) { background-color: #f8fafc; }',
            ].join('\n');
            doc.head.appendChild(style);

            const title = doc.createElement('h1');
            title.textContent = titles[tab];
            doc.body.appendChild(title);

            const meta = doc.createElement('p');
            meta.style.color = '#64748b';
            meta.style.fontSize = '14px';
            meta.style.marginBottom = '20px';
            meta.textContent = `Generated on ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`;
            doc.body.appendChild(meta);

            const headers = Object.keys(tabData.data[0]);
            const table = doc.createElement('table');
            const thead = doc.createElement('thead');
            const headRow = doc.createElement('tr');
            headers.forEach(h => { const th = doc.createElement('th'); th.textContent = h; headRow.appendChild(th); });
            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = doc.createElement('tbody');
            tabData.data.forEach(row => {
                const tr = doc.createElement('tr');
                headers.forEach(h => { const td = doc.createElement('td'); td.textContent = row[h] ?? '-'; tr.appendChild(td); });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            doc.body.appendChild(table);

            setTimeout(() => printWindow.print(), 250);
        },

        downloadCSV(tab) {
            const tabData = this[tab];
            if (!tabData.data.length) return;

            const titles = { shipments: 'shipments', activity: 'activity_logs', otp: 'otp_logs' };
            const headers = Object.keys(tabData.data[0]);
            const csvContent = [
                headers.join(','),
                ...tabData.data.map(row =>
                    headers.map(h => {
                        let cell = row[h] ?? '';
                        cell = String(cell).replace(/"/g, '""');
                        return `"${cell}"`;
                    }).join(',')
                )
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${titles[tab]}_${new Date().toISOString().slice(0,10)}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        // ── Edit modal ──────────────────────────────────────────
        openEditModal() {
            this.form = {
                name: this.vendor.name,
                business_name: this.vendor.business_name || '',
                email: this.vendor.email,
                phone: this.vendor.phone,
                is_active: this.vendor.is_active
            };
            this.errors = {};
            this.showEditModal = true;
        },

        async saveVendor() {
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
                        throw new Error(data.message || 'Failed to update vendor');
                    }
                    return;
                }

                // Update local vendor data
                this.vendor.name = this.form.name;
                this.vendor.business_name = this.form.business_name;
                this.vendor.email = this.form.email;
                this.vendor.is_active = this.form.is_active;

                this.showEditModal = false;

                // Show success notification
                if (window.showToast) {
                    window.showToast('Vendor updated successfully', 'success');
                }
            } catch (error) {
                console.error('Save error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to update vendor', 'error');
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

                this.vendor.is_active = data.is_active;
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

        async restoreVendor() {
            this.restoring = true;
            try {
                const response = await fetch(this.config.restoreEndpoint, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to restore vendor');
                }

                if (window.showToast) {
                    window.showToast(data.message || 'Vendor restored successfully.', 'success');
                }

                window.location.reload();
            } catch (error) {
                console.error('Restore error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to restore vendor.', 'error');
                }
            } finally {
                this.restoring = false;
                this.showRestoreModal = false;
            }
        },

        async deleteVendor() {
            this.deleting = true;
            try {
                const response = await fetch(this.config.deleteEndpoint, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to delete vendor');
                }

                if (window.showToast) {
                    window.showToast(data.message || 'Vendor deleted successfully.', 'success');
                }

                // Redirect to vendors list
                setTimeout(() => {
                    window.location.href = '/admin/vendors';
                }, 1000);
            } catch (error) {
                console.error('Delete error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to delete vendor.', 'error');
                }
            } finally {
                this.deleting = false;
                this.showDeleteModal = false;
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

function getVendorShowConfig() {
    const container = document.querySelector('[data-vendor-show-config]');
    if (!container) return null;

    const rawConfig = container.getAttribute('data-vendor-show-config');
    if (!rawConfig) return null;

    try {
        return JSON.parse(rawConfig);
    } catch (error) {
        console.error('Invalid vendor show config JSON:', error);
        return null;
    }
}

function registerVendorShowPage() {
    if (!window.Alpine) return;

    const config = getVendorShowConfig();
    if (!config) return;

    window.vendorShowConfig = config;
    Alpine.data('vendorShow', vendorShow);
}

if (window.Alpine) {
    registerVendorShowPage();
} else {
    document.addEventListener('alpine:init', registerVendorShowPage);
}
