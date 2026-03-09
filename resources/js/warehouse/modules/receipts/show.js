import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-receipt-show-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-receipt-show-config', null);
    if (!config) {
        console.error('Invalid warehouse receipt show config JSON');
    }

    return config;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function endpointWithItem(urlTemplate, itemId) {
    return (urlTemplate || '').replace('__ITEM__', String(itemId));
}

function endpointWithId(urlTemplate, id) {
    return (urlTemplate || '').replace('__ID__', String(id));
}

function registerWarehouseReceiptShowPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.finalize_url) return;

    window.Alpine.data('warehouseReceiptShowPage', () => ({
        activeTab: 'overview',
        saving: false,
        showFinalizeModal: false,
        finalizeNotes: '',
        approvalReason: '',
        canApproveDiscrepancy: Boolean(config.can_approve_discrepancy),
        receipt: config.receipt || null,
        items: Array.isArray(config.items) ? config.items : [],
        pendingFiles: {},
        removePhotoMap: {},

        // Invoice state
        invoice: config.invoice || null,
        invoiceHistory: Array.isArray(config.invoice_history) ? config.invoice_history : [],
        invoiceModalOpen: false,
        invoiceUiError: '',
        invoiceForm: {
            pickup_fee: '',
            transport_fee: '',
            handling_fee: '',
            other_fee: '',
            notes: '',
            send_now: false,
            submitting: false,
        },
        invoiceTable: {
            search: '',
            sortBy: 'created_at',
            sortDirection: 'desc',
            page: 1,
            perPage: 10,
            statusFilter: '',
        },
        showCancelInvoiceModal: false,
        cancelInvoiceId: null,
        cancelInvoiceReason: '',
        cancelInvoiceLoading: false,
        isSuperAdmin: Boolean(config.is_super_admin),
        canCreateInvoice: Boolean(config.can_create_invoice),
        canEditInvoice: Boolean(config.can_edit_invoice),
        canViewInvoice: Boolean(config.can_view_invoice),

        // Payments state
        paymentsLoaded: false,
        paymentsData: { payments: [], summary: { total_invoiced: 0, total_paid: 0, balance_due: 0 } },
        paymentSearch: '',
        paymentSortBy: 'payment_date',
        paymentSortDir: 'desc',
        paymentPage: 1,
        paymentPerPage: 10,
        paymentForm: {
            open: false,
            submitting: false,
            amount: '',
            payment_method: '',
            reference_number: '',
            notes: '',
            payment_date: '',
        },
        voidConfirm: {
            open: false,
            paymentId: null,
            loading: false,
        },

        receiveModal: { open: false, itemId: null, itemIndex: -1 },

        init() {
            if (!this.receipt && this.items.length > 0) {
                this.receipt = { status: 'draft' };
            }
        },

        isFinalized() {
            return (this.receipt?.status || '') === 'finalized';
        },

        receiptStatusLabel() {
            const status = this.receipt?.status || 'draft';
            switch (status) {
                case 'discrepancy_open':
                    return 'Discrepancy Open';
                case 'finalized':
                    return 'Finalized';
                default:
                    return 'Draft';
            }
        },

        hasDiscrepancies() {
            return this.items.some((item) => (item.discrepancy_type || 'none') !== 'none');
        },

        setItemFiles(itemId, event) {
            const files = Array.from(event?.target?.files || []);
            this.pendingFiles[itemId] = files;
        },

        isPhotoMarkedForRemoval(itemId, photoId) {
            const selected = this.removePhotoMap[itemId] || [];
            return selected.includes(Number(photoId));
        },

        toggleRemovePhoto(itemId, photoId, checked) {
            const normalizedItemId = Number(itemId);
            const normalizedPhotoId = Number(photoId);
            const current = Array.isArray(this.removePhotoMap[normalizedItemId])
                ? this.removePhotoMap[normalizedItemId]
                : [];

            if (checked) {
                this.removePhotoMap[normalizedItemId] = Array.from(new Set([...current, normalizedPhotoId]));
                return;
            }

            this.removePhotoMap[normalizedItemId] = current.filter((id) => Number(id) !== normalizedPhotoId);
        },

        openFinalizeModal() {
            if (this.isFinalized()) return;
            this.showFinalizeModal = true;
        },

        async saveItem(itemId) {
            if (this.isFinalized()) {
                window.showToast?.('Receipt is finalized and cannot be edited.', 'warning');
                return;
            }

            const item = this.items.find((row) => Number(row.shipment_item_id) === Number(itemId));
            if (!item) return;

            this.saving = true;
            try {
                const formData = new FormData();
                formData.append('received_quantity', String(item.received_quantity ?? 0));
                formData.append('damaged_quantity', String(item.damaged_quantity ?? 0));
                formData.append('condition_status', item.condition_status || 'ok');
                formData.append('notes', item.notes || '');
                formData.append('_token', csrfToken());

                (this.pendingFiles[itemId] || []).forEach((file) => {
                    formData.append('photos[]', file);
                });

                (this.removePhotoMap[itemId] || []).forEach((photoId) => {
                    formData.append('remove_photo_ids[]', String(photoId));
                });

                const response = await fetch(endpointWithItem(config.save_item_url, itemId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to save item.');
                }

                if (result.data?.item) {
                    Object.assign(item, result.data.item);
                }
                if (result.data?.receipt) {
                    this.receipt = result.data.receipt;
                }

                this.pendingFiles[itemId] = [];
                this.removePhotoMap[itemId] = [];
                window.showToast?.(result.message || 'Item saved successfully.', 'success');
                if (this.receiveModal.open && this.receiveModal.itemId === Number(itemId)) {
                    this.closeReceiveModal();
                }
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to save item.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async printLabel(itemId) {
            if (this.isFinalized()) {
                window.showToast?.('Receipt is finalized and label state is locked.', 'warning');
            }

            this.saving = true;
            try {
                const response = await fetch(endpointWithItem(config.print_label_url, itemId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to generate label.');
                }

                const html = result?.data?.label_html || '';
                if (!html) {
                    throw new Error('Label HTML payload is empty.');
                }

                const popup = window.open('', '_blank', 'width=900,height=650');
                if (!popup) {
                    throw new Error('Pop-up blocked. Please allow pop-ups to print labels.');
                }

                popup.document.open();
                popup.document.write(html);
                popup.document.close();

                const item = this.items.find((row) => Number(row.shipment_item_id) === Number(itemId));
                if (item) {
                    item.barcode_value = result?.data?.barcode_value || item.barcode_value;
                    item.barcode_print_count = Number(result?.data?.print_count || item.barcode_print_count || 0);
                }

                window.showToast?.(result.message || 'Label generated.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to print label.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async finalizeReceipt() {
            if (this.isFinalized()) {
                this.showFinalizeModal = false;
                return;
            }

            if (this.hasDiscrepancies() && !this.canApproveDiscrepancy) {
                window.showToast?.('Discrepancy finalization requires warehouse manager approval.', 'error');
                return;
            }

            this.saving = true;
            try {
                const payload = {
                    notes: this.finalizeNotes || null,
                    approval_reason: this.approvalReason || null,
                };

                const response = await fetch(config.finalize_url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to finalize receipt.');
                }

                if (result.data?.receipt) {
                    this.receipt = result.data.receipt;
                } else {
                    this.receipt = { ...(this.receipt || {}), status: 'finalized' };
                }

                this.showFinalizeModal = false;
                window.showToast?.(result.message || 'Receipt finalized successfully.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to finalize receipt.', 'error');
            } finally {
                this.saving = false;
            }
        },

        // ─── Invoice helpers ─────────────────────────────────────────────────

        activeInvoice() {
            const active = ['pending', 'sent', 'accepted'];
            return this.invoiceHistory.find((r) => active.includes(r.status)) || null;
        },

        hasActiveInvoice() {
            return Boolean(this.activeInvoice());
        },

        invoiceStatusClass(status) {
            if (status === 'pending' || status === 'sent') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            if (status === 'accepted') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (status === 'rejected' || status === 'cancelled') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            return 'bg-slate-50 text-slate-600 ring-1 ring-slate-200';
        },

        invoiceStatusLabel(status) {
            switch (status) {
                case 'pending':   return 'Pending';
                case 'sent':      return 'Sent';
                case 'accepted':  return 'Accepted';
                case 'rejected':  return 'Rejected';
                case 'cancelled': return 'Cancelled';
                default: return status ? status.charAt(0).toUpperCase() + status.slice(1) : '-';
            }
        },

        openCreateInvoiceModal() {
            if (this.hasActiveInvoice()) {
                window.showToast?.('This shipment already has an active invoice.', 'error');
                return;
            }
            this.invoiceForm = { pickup_fee: '', transport_fee: '', handling_fee: '', other_fee: '', notes: '', send_now: false, submitting: false };
            this.invoiceUiError = '';
            this.invoiceModalOpen = true;
        },

        closeCreateInvoiceModal() {
            this.invoiceModalOpen = false;
            this.invoiceUiError = '';
        },

        async createInvoice() {
            this.invoiceUiError = '';
            this.invoiceForm.submitting = true;
            try {
                const response = await fetch(config.invoice_store_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        pickup_fee: this.invoiceForm.pickup_fee,
                        transport_fee: this.invoiceForm.transport_fee || 0,
                        handling_fee: this.invoiceForm.handling_fee || 0,
                        other_fee: this.invoiceForm.other_fee || 0,
                        notes: this.invoiceForm.notes || null,
                        send_now: this.invoiceForm.send_now ? 1 : 0,
                    }),
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to create invoice');

                window.showToast?.(data.message || 'Invoice created successfully', 'success');
                this.closeCreateInvoiceModal();
                window.location.reload();
            } catch (error) {
                console.error('Create invoice error:', error);
                this.invoiceUiError = error.message || 'Failed to create invoice';
                window.showToast?.(error.message || 'Failed to create invoice', 'error');
            } finally {
                this.invoiceForm.submitting = false;
            }
        },

        async sendInvoice(inv) {
            const url = inv?.send_url || this.invoice?.send_url;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to send invoice');
                window.showToast?.(data.message || 'Invoice sent to vendor', 'success');
                window.location.reload();
            } catch (error) {
                window.showToast?.(error.message || 'Failed to send invoice', 'error');
            }
        },

        openCancelInvoiceModal(inv) {
            const target = inv || this.invoice;
            if (!target) return;
            this.cancelInvoiceId = target.id;
            this.cancelInvoiceReason = '';
            this.cancelInvoiceLoading = false;
            this.showCancelInvoiceModal = true;
        },

        async confirmCancelInvoice() {
            if (!this.cancelInvoiceId) return;
            this.cancelInvoiceLoading = true;

            const inv = this.invoiceHistory.find((r) => r.id === this.cancelInvoiceId) || this.invoice;
            const url = inv?.cancel_url;
            if (!url) {
                this.cancelInvoiceLoading = false;
                return;
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                    body: JSON.stringify({ cancel_reason: this.cancelInvoiceReason.trim() || null }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to cancel invoice');
                this.showCancelInvoiceModal = false;
                window.showToast?.(data.message || 'Invoice cancelled', 'success');
                window.location.reload();
            } catch (error) {
                this.cancelInvoiceLoading = false;
                window.showToast?.(error.message || 'Failed to cancel invoice', 'error');
            }
        },

        async adminAcceptInvoice(inv) {
            const target = inv || this.invoice;
            if (!target?.admin_accept_url) return;

            const confirmed = window.confirm(
                'Accept this invoice on behalf of the vendor?\n\n' +
                'This is an admin override. The invoice will be marked as accepted ' +
                'and the shipment will proceed to the next stage.'
            );
            if (!confirmed) return;

            const adminNotes = window.prompt('Optional notes for this override:') ?? '';
            if (adminNotes === null) return;

            try {
                const response = await fetch(target.admin_accept_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                    body: JSON.stringify({ admin_notes: adminNotes || null }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to accept invoice');
                window.showToast?.(data.message || 'Invoice accepted on behalf of vendor', 'success');
                window.location.reload();
            } catch (error) {
                window.showToast?.(error.message || 'Failed to accept invoice', 'error');
            }
        },

        // Invoice table helpers
        filteredInvoiceRows() {
            let rows = [...this.invoiceHistory];
            const q = (this.invoiceTable.search || '').trim().toLowerCase();
            if (q) {
                rows = rows.filter((r) =>
                    (r.invoice_number || '').toLowerCase().includes(q) ||
                    (r.status || '').toLowerCase().includes(q)
                );
            }
            if (this.invoiceTable.statusFilter) {
                rows = rows.filter((r) => r.status === this.invoiceTable.statusFilter);
            }
            const dir = this.invoiceTable.sortDirection === 'asc' ? 1 : -1;
            const key = this.invoiceTable.sortBy;
            rows.sort((a, b) => {
                const av = (a[key] ?? '').toString().toLowerCase();
                const bv = (b[key] ?? '').toString().toLowerCase();
                return av < bv ? -dir : av > bv ? dir : 0;
            });
            return rows;
        },

        paginatedInvoiceRows() {
            const rows = this.filteredInvoiceRows();
            const start = (this.invoiceTable.page - 1) * this.invoiceTable.perPage;
            return rows.slice(start, start + this.invoiceTable.perPage);
        },

        invoiceMeta() {
            const rows = this.filteredInvoiceRows();
            const total = rows.length;
            const lastPage = Math.max(1, Math.ceil(total / this.invoiceTable.perPage));
            const page = Math.min(this.invoiceTable.page, lastPage);
            const from = total === 0 ? 0 : (page - 1) * this.invoiceTable.perPage + 1;
            const to = Math.min(page * this.invoiceTable.perPage, total);
            return { total, page, lastPage, from, to };
        },

        sortInvoice(field) {
            if (this.invoiceTable.sortBy === field) {
                this.invoiceTable.sortDirection = this.invoiceTable.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.invoiceTable.sortBy = field;
                this.invoiceTable.sortDirection = 'asc';
            }
            this.invoiceTable.page = 1;
        },

        // ─── Payment helpers ─────────────────────────────────────────────────

        async loadPayments() {
            try {
                const url = config.payments_data_url;
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) throw new Error('Failed to load payments');
                const data = await response.json();
                this.paymentsData = data;
            } catch (e) {
                console.error('Failed to load payments:', e);
            } finally {
                this.paymentsLoaded = true;
            }
        },

        switchTab(tab) {
            this.activeTab = tab;
            if (tab === 'payments' && !this.paymentsLoaded) {
                this.loadPayments();
            }
        },

        filteredPayments() {
            let list = this.paymentsData.payments || [];
            if (this.paymentSearch) {
                const q = this.paymentSearch.toLowerCase();
                list = list.filter((p) =>
                    (p.payment_date || '').toLowerCase().includes(q) ||
                    (p.method_label || '').toLowerCase().includes(q) ||
                    (p.reference_number || '').toLowerCase().includes(q) ||
                    (p.invoice_number || '').toLowerCase().includes(q) ||
                    (p.recorded_by || '').toLowerCase().includes(q)
                );
            }
            const dir = this.paymentSortDir === 'asc' ? 1 : -1;
            const key = this.paymentSortBy;
            list = [...list].sort((a, b) => {
                const av = (a[key] ?? '').toString().toLowerCase();
                const bv = (b[key] ?? '').toString().toLowerCase();
                return av < bv ? -dir : av > bv ? dir : 0;
            });
            return list;
        },

        paginatedPayments() {
            const all = this.filteredPayments();
            const start = (this.paymentPage - 1) * this.paymentPerPage;
            return all.slice(start, start + this.paymentPerPage);
        },

        paymentLastPage() {
            return Math.max(1, Math.ceil(this.filteredPayments().length / this.paymentPerPage));
        },

        sortPayments(col) {
            if (this.paymentSortBy === col) {
                this.paymentSortDir = this.paymentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.paymentSortBy = col;
                this.paymentSortDir = 'desc';
            }
            this.paymentPage = 1;
        },

        openRecordPaymentModal() {
            this.paymentForm = {
                open: true,
                submitting: false,
                amount: '',
                payment_method: '',
                reference_number: '',
                notes: '',
                payment_date: new Date().toISOString().slice(0, 10),
            };
        },

        async submitPayment() {
            this.paymentForm.submitting = true;
            try {
                const response = await fetch(config.payments_store_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        amount: this.paymentForm.amount,
                        payment_method: this.paymentForm.payment_method,
                        reference_number: this.paymentForm.reference_number || null,
                        notes: this.paymentForm.notes || null,
                        payment_date: this.paymentForm.payment_date,
                    }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to record payment');
                window.showToast?.(data.message || 'Payment recorded', 'success');
                this.paymentForm = { open: false, submitting: false, amount: '', payment_method: '', reference_number: '', notes: '', payment_date: '' };
                await this.loadPayments();
            } catch (e) {
                console.error('Failed to record payment:', e);
                window.showToast?.(e.message || 'Failed to record payment', 'error');
            } finally {
                this.paymentForm.submitting = false;
            }
        },

        voidPayment(paymentId) {
            this.voidConfirm = { open: true, paymentId, loading: false };
        },

        async confirmVoidPayment() {
            this.voidConfirm.loading = true;
            try {
                const url = endpointWithId(config.payment_destroy_url, this.voidConfirm.paymentId);
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to void payment');
                this.voidConfirm = { open: false, paymentId: null, loading: false };
                window.showToast?.(data.message || 'Payment voided', 'success');
                await this.loadPayments();
            } catch (e) {
                console.error('Failed to void payment:', e);
                window.showToast?.(e.message || 'Failed to void payment', 'error');
                this.voidConfirm.loading = false;
            }
        },

        formatCurrency(value) {
            if (value == null || isNaN(Number(value))) return '0.00';
            return Number(value).toFixed(2);
        },

        paymentDownloadUrl(id) {
            return endpointWithId(config.payment_download_url, id);
        },

        paymentPrintUrl(id) {
            return endpointWithId(config.payment_print_url, id);
        },

        openReceiveModal(itemId) {
            const idx = this.items.findIndex((i) => Number(i.shipment_item_id) === Number(itemId));
            this.receiveModal = { open: true, itemId: Number(itemId), itemIndex: idx };
        },

        closeReceiveModal() {
            this.receiveModal = { open: false, itemId: null, itemIndex: -1 };
        },
    }));
}

if (window.Alpine) {
    registerWarehouseReceiptShowPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseReceiptShowPage);
}
