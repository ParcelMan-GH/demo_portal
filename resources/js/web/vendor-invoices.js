import { apiRequest, clearToken, getToken } from './auth-client';

const INVOICE_STATUS_OPTIONS = [
    { value: 'pending', label: 'Pending' },
    { value: 'sent', label: 'Sent' },
    { value: 'accepted', label: 'Accepted' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'cancelled', label: 'Cancelled' },
];

const INVOICE_SORT_FIELDS = [
    { value: 'created_at', label: 'Created At' },
    { value: 'updated_at', label: 'Updated At' },
    { value: 'invoice_number', label: 'Invoice Number' },
    { value: 'status', label: 'Status' },
    { value: 'total_amount', label: 'Total Amount' },
    { value: 'sent_at', label: 'Sent At' },
    { value: 'accepted_at', label: 'Accepted At' },
    { value: 'rejected_at', label: 'Rejected At' },
    { value: 'cancelled_at', label: 'Cancelled At' },
];

function flattenErrors(payload) {
    const errors = payload?.errors;
    if (!errors || typeof errors !== 'object') {
        return [];
    }

    return Object.values(errors)
        .flatMap((value) => (Array.isArray(value) ? value : [value]))
        .filter((value) => typeof value === 'string' && value.trim() !== '');
}

function statusLabel(status) {
    if (!status) {
        return '-';
    }

    return String(status)
        .split('_')
        .map((token) => token.charAt(0).toUpperCase() + token.slice(1))
        .join(' ');
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatMoney(value, currency = 'GHS') {
    const amount = Number(value);
    if (Number.isNaN(amount)) {
        return '-';
    }

    try {
        return new Intl.NumberFormat('en-GH', {
            style: 'currency',
            currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `${amount.toFixed(2)} ${currency}`;
    }
}

async function ensureVendorSessionOrRedirect() {
    const token = getToken('vendor');
    if (!token) {
        window.location.href = '/vendor/login';
        return false;
    }

    const profile = await apiRequest('/api/v1/vendor/profile', { role: 'vendor' });
    if (!profile.success) {
        clearToken('vendor');
        window.location.href = '/vendor/login';
        return false;
    }

    return true;
}

function vendorInvoicesListPage() {
    return {
        loading: true,
        alert: null,
        validationErrors: [],
        invoices: [],
        shipments: [],
        invoiceOptions: [],
        statuses: INVOICE_STATUS_OPTIONS,
        sortFields: INVOICE_SORT_FIELDS,
        filters: {
            shipment_id: '',
            invoice_number: '',
            search: '',
            from_date: '',
            to_date: '',
            status: [],
            limit: 15,
            offset: 0,
            sort_by: 'created_at',
            sort_order: 'desc',
        },
        pagination: {
            offset: 0,
            limit: 15,
            total: 0,
            has_more: false,
            next_offset: null,
            current_page: 1,
            last_page: 1,
            per_page: 15,
        },

        async init() {
            if (!(await ensureVendorSessionOrRedirect())) {
                return;
            }

            await Promise.all([
                this.loadShipmentOptions(),
                this.loadInvoiceOptions(),
            ]);
            await this.loadInvoices();
        },

        statusLabel,
        formatDateTime,
        formatMoney,

        get hasAdvancedFilters() {
            return Boolean(
                this.filters.shipment_id ||
                this.filters.invoice_number ||
                this.filters.search.trim() ||
                this.filters.from_date ||
                this.filters.to_date ||
                (this.filters.status || []).length
            );
        },

        showAlert(type, message) {
            this.alert = { type, message };
        },

        setValidationErrors(payload) {
            this.validationErrors = flattenErrors(payload);
        },

        clearValidationErrors() {
            this.validationErrors = [];
        },

        buildQueryString() {
            const params = new URLSearchParams();
            if (this.filters.shipment_id) {
                params.append('shipment_id', String(this.filters.shipment_id));
            }
            if (this.filters.invoice_number) {
                params.append('invoice_number', this.filters.invoice_number);
            }
            if (this.filters.search.trim()) {
                params.append('search', this.filters.search.trim());
            }
            if (this.filters.from_date) {
                params.append('from_date', this.filters.from_date);
            }
            if (this.filters.to_date) {
                params.append('to_date', this.filters.to_date);
            }
            this.filters.status.forEach((value) => {
                if (value) {
                    params.append('status[]', value);
                }
            });
            params.append('limit', String(this.filters.limit));
            params.append('offset', String(this.filters.offset));
            params.append('sort_by', this.filters.sort_by);
            params.append('sort_order', this.filters.sort_order);
            return params.toString();
        },

        async loadShipmentOptions() {
            const result = await apiRequest('/api/v1/vendor/shipments?limit=100&offset=0&sort_by=created_at&sort_order=desc', {
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                return;
            }

            this.shipments = Array.isArray(result.payload?.data?.shipments)
                ? result.payload.data.shipments.map((shipment) => ({
                    id: shipment.id,
                    shipment_number: shipment.shipment_number,
                }))
                : [];
        },

        async loadInvoiceOptions() {
            const shipmentFilter = this.filters.shipment_id
                ? `&shipment_id=${encodeURIComponent(String(this.filters.shipment_id))}`
                : '';

            const result = await apiRequest(
                `/api/v1/vendor/invoices?limit=100&offset=0&sort_by=created_at&sort_order=desc${shipmentFilter}`,
                { role: 'vendor' }
            );

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.invoiceOptions = [];
                return;
            }

            const invoices = Array.isArray(result.payload?.data?.invoices)
                ? result.payload.data.invoices
                : [];

            this.invoiceOptions = invoices.map((invoice) => ({
                id: invoice.id,
                invoice_number: invoice.invoice_number,
                shipment_id: invoice.shipment_id,
                shipment_number: invoice.shipment_number,
            }));

            if (this.filters.invoice_number) {
                const stillExists = this.invoiceOptions.some(
                    (entry) => entry.invoice_number === this.filters.invoice_number
                );
                if (!stillExists) {
                    this.filters.invoice_number = '';
                }
            }
        },

        async onShipmentChange() {
            this.filters.invoice_number = '';
            await this.loadInvoiceOptions();
            await this.applyFilters();
        },

        async loadInvoices() {
            this.loading = true;
            this.alert = null;
            this.clearValidationErrors();

            const result = await apiRequest(`/api/v1/vendor/invoices?${this.buildQueryString()}`, {
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                this.loading = false;
                return;
            }

            const data = result.payload?.data || {};
            this.invoices = Array.isArray(data.invoices) ? data.invoices : [];
            this.pagination = data.pagination || this.pagination;
            this.filters.offset = Number(this.pagination.offset || 0);
            this.filters.limit = Number(this.pagination.limit || this.filters.limit);
            this.loading = false;
        },

        async applyFilters() {
            this.filters.offset = 0;
            await this.loadInvoices();
        },

        async resetFilters() {
            this.filters.shipment_id = '';
            this.filters.invoice_number = '';
            this.filters.search = '';
            this.filters.from_date = '';
            this.filters.to_date = '';
            this.filters.status = [];
            this.filters.limit = 15;
            this.filters.offset = 0;
            this.filters.sort_by = 'created_at';
            this.filters.sort_order = 'desc';
            await this.loadInvoiceOptions();
            await this.loadInvoices();
        },

        async nextPage() {
            if (!this.pagination.has_more || this.pagination.next_offset === null) {
                return;
            }

            this.filters.offset = Number(this.pagination.next_offset);
            await this.loadInvoices();
        },

        async previousPage() {
            if (this.filters.offset <= 0) {
                return;
            }

            this.filters.offset = Math.max(0, this.filters.offset - this.filters.limit);
            await this.loadInvoices();
        },

        async acceptInvoice(invoice) {
            if (!invoice?.id || invoice.status !== 'sent') {
                return;
            }

            const vendorNotes = window.prompt('Vendor notes (optional):', '') ?? '';
            const result = await apiRequest(`/api/v1/vendor/invoices/${invoice.id}/accept`, {
                method: 'POST',
                role: 'vendor',
                data: {
                    vendor_notes: vendorNotes.trim() || null,
                },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadInvoiceOptions();
            await this.loadInvoices();
        },

        async rejectInvoice(invoice) {
            if (!invoice?.id || invoice.status !== 'sent') {
                return;
            }

            const rejectionReason = window.prompt('Rejection reason (required):', '');
            if (!rejectionReason || !rejectionReason.trim()) {
                this.showAlert('error', 'Rejection reason is required.');
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/invoices/${invoice.id}/reject`, {
                method: 'POST',
                role: 'vendor',
                data: {
                    rejection_reason: rejectionReason.trim(),
                },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadInvoiceOptions();
            await this.loadInvoices();
        },
    };
}

function vendorInvoiceShowPage() {
    return {
        loading: true,
        invoiceId: null,
        invoice: null,
        alert: null,
        validationErrors: [],

        async init() {
            this.invoiceId = this.$el.dataset.invoiceId;

            if (!(await ensureVendorSessionOrRedirect())) {
                return;
            }

            await this.loadInvoice();
            this.loading = false;
        },

        statusLabel,
        formatDateTime,
        formatMoney,

        get canRespond() {
            return this.invoice?.status === 'sent';
        },

        showAlert(type, message) {
            this.alert = { type, message };
        },

        setValidationErrors(payload) {
            this.validationErrors = flattenErrors(payload);
        },

        clearValidationErrors() {
            this.validationErrors = [];
        },

        async loadInvoice() {
            this.alert = null;
            this.clearValidationErrors();

            const result = await apiRequest(`/api/v1/vendor/invoices/${this.invoiceId}`, {
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                return;
            }

            this.invoice = result.payload?.data?.invoice || null;
        },

        async acceptInvoice() {
            if (!this.invoice?.id || !this.canRespond) {
                return;
            }

            const vendorNotes = window.prompt('Vendor notes (optional):', '') ?? '';
            const result = await apiRequest(`/api/v1/vendor/invoices/${this.invoice.id}/accept`, {
                method: 'POST',
                role: 'vendor',
                data: {
                    vendor_notes: vendorNotes.trim() || null,
                },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadInvoice();
        },

        async rejectInvoice() {
            if (!this.invoice?.id || !this.canRespond) {
                return;
            }

            const rejectionReason = window.prompt('Rejection reason (required):', '');
            if (!rejectionReason || !rejectionReason.trim()) {
                this.showAlert('error', 'Rejection reason is required.');
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/invoices/${this.invoice.id}/reject`, {
                method: 'POST',
                role: 'vendor',
                data: {
                    rejection_reason: rejectionReason.trim(),
                },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadInvoice();
        },
    };
}

function registerVendorInvoiceComponents() {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.data('vendorInvoicesListPage', vendorInvoicesListPage);
    window.Alpine.data('vendorInvoiceShowPage', vendorInvoiceShowPage);
}

if (window.Alpine) {
    registerVendorInvoiceComponents();
} else {
    document.addEventListener('alpine:init', registerVendorInvoiceComponents);
}
