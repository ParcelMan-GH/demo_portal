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
    }));
}

if (window.Alpine) {
    registerWarehouseReceiptShowPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseReceiptShowPage);
}
