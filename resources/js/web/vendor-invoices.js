import { apiRequest, clearToken, getToken } from './auth-client';

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

const VENDOR_STATUS_LABELS = {
    pending: 'Pending',
    sent: 'Pending',
    accepted: 'Accepted',
    rejected: 'Rejected',
    cancelled: 'Cancelled',
};

function vendorStatusLabel(status) {
    if (!status) {
        return '-';
    }
    return VENDOR_STATUS_LABELS[status] || statusLabel(status);
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
        invoices: [],
        activeTab: 'all',
        tabCounts: { all: 0, pending: 0, accepted: 0, rejected: 0, cancelled: 0 },
        filters: {
            limit: 15,
            offset: 0,
        },
        pagination: {
            offset: 0,
            limit: 15,
            total: 0,
            has_more: false,
            next_offset: null,
            current_page: 1,
            last_page: 1,
        },

        async init() {
            if (!(await ensureVendorSessionOrRedirect())) {
                return;
            }

            await Promise.all([this.loadInvoices(), this.loadTabCounts()]);
        },

        vendorStatusLabel,
        formatDateTime,
        formatMoney,

        formatDate(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '-';
            return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        statusColor(status) {
            const map = { pending: 'orange', sent: 'orange', accepted: 'green', rejected: 'red', cancelled: 'gray' };
            return map[status] || 'gray';
        },

        buildQueryString() {
            const params = new URLSearchParams();
            if (this.activeTab !== 'all') {
                if (this.activeTab === 'pending') {
                    params.append('status[]', 'pending');
                    params.append('status[]', 'sent');
                } else {
                    params.append('status[]', this.activeTab);
                }
            }
            params.append('limit', String(this.filters.limit));
            params.append('offset', String(this.filters.offset));
            params.append('sort_by', 'created_at');
            params.append('sort_order', 'desc');
            return params.toString();
        },

        async loadInvoices() {
            this.loading = true;

            const result = await apiRequest(`/api/v1/vendor/invoices?${this.buildQueryString()}`, {
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.loading = false;
                return;
            }

            const data = result.payload?.data || {};
            this.invoices = Array.isArray(data.invoices) ? data.invoices : [];
            this.pagination = data.pagination || this.pagination;
            this.filters.offset = Number(this.pagination.offset || 0);
            this.loading = false;
        },

        async loadTabCounts() {
            const tabs = [
                { key: 'all', params: '' },
                { key: 'pending', params: 'status[]=pending&status[]=sent' },
                { key: 'accepted', params: 'status[]=accepted' },
                { key: 'rejected', params: 'status[]=rejected' },
                { key: 'cancelled', params: 'status[]=cancelled' },
            ];

            const results = await Promise.all(
                tabs.map((tab) =>
                    apiRequest(`/api/v1/vendor/invoices?limit=1&offset=0${tab.params ? '&' + tab.params : ''}`, {
                        role: 'vendor',
                    })
                )
            );

            results.forEach((result, i) => {
                if (result.success) {
                    this.tabCounts[tabs[i].key] = result.payload?.data?.pagination?.total || 0;
                }
            });
        },

        async switchTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            this.filters.offset = 0;
            await this.loadInvoices();
        },

        async nextPage() {
            if (!this.pagination.has_more || this.pagination.next_offset === null) return;
            this.filters.offset = Number(this.pagination.next_offset);
            await this.loadInvoices();
        },

        async previousPage() {
            if (this.filters.offset <= 0) return;
            this.filters.offset = Math.max(0, this.filters.offset - this.filters.limit);
            await this.loadInvoices();
        },

        async acceptInvoice(invoice) {
            if (!invoice?.id || invoice.status !== 'sent') return;

            const vendorNotes = window.prompt('Vendor notes (optional):', '') ?? '';
            const result = await apiRequest(`/api/v1/vendor/invoices/${invoice.id}/accept`, {
                method: 'POST',
                role: 'vendor',
                data: { vendor_notes: vendorNotes.trim() || null },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                if (window.vendorToast) window.vendorToast('error', result.message);
                return;
            }

            if (window.vendorToast) window.vendorToast('success', result.message);
            await Promise.all([this.loadInvoices(), this.loadTabCounts()]);
        },

        async rejectInvoice(invoice) {
            if (!invoice?.id || invoice.status !== 'sent') return;

            const rejectionReason = window.prompt('Rejection reason (required):', '');
            if (!rejectionReason || !rejectionReason.trim()) {
                if (window.vendorToast) window.vendorToast('error', 'Rejection reason is required.');
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/invoices/${invoice.id}/reject`, {
                method: 'POST',
                role: 'vendor',
                data: { rejection_reason: rejectionReason.trim() },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                if (window.vendorToast) window.vendorToast('error', result.message);
                return;
            }

            if (window.vendorToast) window.vendorToast('success', result.message);
            await Promise.all([this.loadInvoices(), this.loadTabCounts()]);
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
        actionDialog: null,
        actionInput: '',
        actionBusy: false,
        downloadingPdf: false,

        async init() {
            this.invoiceId = this.$el.dataset.invoiceId;

            if (!(await ensureVendorSessionOrRedirect())) {
                return;
            }

            await this.loadInvoice();
            this.loading = false;
        },

        statusLabel,
        vendorStatusLabel,
        formatDateTime,
        formatMoney,

        get canRespond() {
            return this.invoice?.status === 'sent';
        },

        get heroClass() {
            return 'hero-invoice';
        },

        get heroMessage() {
            const messages = {
                pending: { title: 'Pending Review', text: 'This invoice is being prepared and has not been sent yet.' },
                sent: { title: 'Action Required', text: 'Please review the fee breakdown and accept or reject this invoice.' },
                accepted: { title: 'Invoice Accepted', text: 'You have accepted this invoice.' },
                rejected: { title: 'Invoice Rejected', text: 'This invoice was rejected.' },
                cancelled: { title: 'Invoice Cancelled', text: 'This invoice has been cancelled by the administrator.' },
            };
            return messages[this.invoice?.status] || { title: 'Invoice', text: '' };
        },

        get summaryHeaderClass() {
            const map = {
                pending: 'inv-header-orange-light',
                sent: 'inv-header-orange',
                accepted: 'inv-header-green',
                rejected: 'inv-header-red',
                cancelled: 'inv-header-red',
            };
            return map[this.invoice?.status] || 'inv-header-orange';
        },

        get hasNotes() {
            if (!this.invoice) return false;
            return !!(this.invoice.notes || this.invoice.vendor_notes || this.invoice.rejection_reason || this.invoice.cancel_reason);
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

        openActionDialog(type) {
            if (!this.invoice?.id || !this.canRespond) {
                return;
            }

            this.actionInput = '';
            this.actionBusy = false;

            if (type === 'accept') {
                this.actionDialog = {
                    type: 'accept',
                    title: 'Accept Invoice',
                    message: `Accept invoice ${this.invoice.invoice_number || ''}? This action cannot be undone.`,
                    inputLabel: 'Vendor Notes (optional)',
                    inputPlaceholder: 'Add any notes for this invoice...',
                    inputRequired: false,
                    confirmLabel: 'Accept Invoice',
                    confirmClass: 'accept',
                };
            } else {
                this.actionDialog = {
                    type: 'reject',
                    title: 'Reject Invoice',
                    message: `Reject invoice ${this.invoice.invoice_number || ''}? This action cannot be undone.`,
                    inputLabel: 'Rejection Reason (required)',
                    inputPlaceholder: 'Please provide a reason for rejection...',
                    inputRequired: true,
                    confirmLabel: 'Reject Invoice',
                    confirmClass: 'reject',
                };
            }
        },

        closeActionDialog() {
            this.actionDialog = null;
            this.actionInput = '';
            this.actionBusy = false;
        },

        async confirmAction() {
            if (!this.actionDialog || this.actionBusy) {
                return;
            }

            const { type, inputRequired } = this.actionDialog;
            const inputValue = this.actionInput.trim();

            if (inputRequired && !inputValue) {
                this.showAlert('error', 'Rejection reason is required.');
                return;
            }

            this.actionBusy = true;

            if (type === 'accept') {
                await this._doAccept(inputValue || null);
            } else {
                await this._doReject(inputValue);
            }

            this.closeActionDialog();
        },

        async _doAccept(vendorNotes) {
            const result = await apiRequest(`/api/v1/vendor/invoices/${this.invoice.id}/accept`, {
                method: 'POST',
                role: 'vendor',
                data: { vendor_notes: vendorNotes },
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

        async _doReject(rejectionReason) {
            const result = await apiRequest(`/api/v1/vendor/invoices/${this.invoice.id}/reject`, {
                method: 'POST',
                role: 'vendor',
                data: { rejection_reason: rejectionReason },
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

        async downloadPdf() {
            if (!this.invoice?.id || this.downloadingPdf) {
                return;
            }

            const token = getToken('vendor');
            if (!token) {
                window.location.href = '/vendor/login';
                return;
            }

            this.downloadingPdf = true;

            try {
                const response = await fetch(`/api/v1/vendor/invoices/${this.invoice.id}/pdf`, {
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: 'application/pdf',
                    },
                });

                if (response.status === 401) {
                    clearToken('vendor');
                    window.location.href = '/vendor/login';
                    return;
                }

                if (!response.ok) {
                    const payload = await response.json().catch(() => null);
                    if (window.vendorToast) {
                        window.vendorToast('error', payload?.message || 'Failed to download invoice.');
                    } else {
                        this.showAlert('error', payload?.message || 'Failed to download invoice.');
                    }
                    return;
                }

                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${this.invoice.invoice_number || 'invoice'}.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch {
                if (window.vendorToast) {
                    window.vendorToast('error', 'Failed to download invoice. Please try again.');
                } else {
                    this.showAlert('error', 'Failed to download invoice. Please try again.');
                }
            } finally {
                this.downloadingPdf = false;
            }
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
