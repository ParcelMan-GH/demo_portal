function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function withItem(urlTemplate, itemId) {
    return (urlTemplate || '').replace('__ITEM__', String(itemId));
}

function registerWarehouseIncomingManifestShowPage() {
    if (!window.Alpine) return;

    const config = window.__incomingManifestConfig || {};
    if (!config.finalize_endpoint) return;

    window.Alpine.data('warehouseIncomingManifestShowPage', () => ({
        activeTab: 'overview',
        loading: false,
        showFinalizeModal: false,
        finalizeNotes: '',
        manifestStatus: config.manifest_status || 'draft',
        items: Array.isArray(config.items) ? config.items : [],

        // Receive modal state
        receiveModal: { open: false, itemId: null, itemIndex: -1 },

        isFinalized() {
            return (this.manifestStatus || '').toLowerCase() === 'received';
        },

        canReceive() {
            return (this.manifestStatus || '').toLowerCase() === 'arrived';
        },

        receivedCount() {
            return this.items.filter((i) => i.received_at).length;
        },

        discrepancyCount() {
            return this.items.filter(
                (i) => i.line_status && !['pending', 'loaded', 'received'].includes(i.line_status),
            ).length;
        },

        statusClass(status) {
            switch ((status || '').toLowerCase()) {
                case 'received':
                    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                case 'short':
                    return 'border-amber-200 bg-amber-50 text-amber-700';
                case 'excess':
                    return 'border-indigo-200 bg-indigo-50 text-indigo-700';
                case 'damaged':
                    return 'border-rose-200 bg-rose-50 text-rose-700';
                case 'loaded':
                    return 'border-blue-200 bg-blue-50 text-blue-700';
                default:
                    return 'border-slate-200 bg-slate-50 text-slate-700';
            }
        },

        statusLabel(status) {
            switch ((status || '').toLowerCase()) {
                case 'received':
                    return 'Received';
                case 'short':
                    return 'Short';
                case 'excess':
                    return 'Excess';
                case 'damaged':
                    return 'Damaged';
                case 'loaded':
                    return 'Loaded';
                default:
                    return 'Pending';
            }
        },

        openReceiveModal(itemId) {
            if (this.isFinalized()) return;
            if (!this.canReceive()) {
                window.showToast?.('Manifest must be marked arrived before receiving.', 'warning');
                return;
            }
            const idx = this.items.findIndex((i) => Number(i.shipment_item_id) === Number(itemId));
            if (idx < 0) return;
            this.receiveModal = { open: true, itemId, itemIndex: idx };
        },

        closeReceiveModal() {
            this.receiveModal = { open: false, itemId: null, itemIndex: -1 };
        },

        async saveItem(itemId) {
            if (this.isFinalized()) {
                window.showToast?.('Manifest receipt is finalized and cannot be changed.', 'warning');
                return;
            }

            if (!this.canReceive()) {
                window.showToast?.('Manifest must be marked arrived before receiving.', 'warning');
                return;
            }

            const row = this.items.find((item) => Number(item.shipment_item_id) === Number(itemId));
            if (!row) return;

            this.loading = true;
            try {
                const response = await fetch(withItem(config.scan_receive_endpoint, itemId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        received_quantity: Number(row.received_quantity ?? 0),
                        line_status: row.line_status || null,
                        notes: row.notes || null,
                    }),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to save receiving line.');
                }

                const updatedLine = result?.data?.line;
                if (updatedLine) {
                    row.received_quantity = Number(updatedLine.received_quantity ?? row.received_quantity ?? 0);
                    row.line_status = updatedLine.line_status || row.line_status;
                    row.notes = updatedLine.notes || row.notes;
                    row.received_at = updatedLine.received_at || row.received_at;
                }

                this.closeReceiveModal();
                window.showToast?.(result.message || 'Line saved successfully.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to save receiving line.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async finalizeReceipt() {
            if (this.isFinalized()) {
                this.showFinalizeModal = false;
                return;
            }

            if (!this.canReceive()) {
                this.showFinalizeModal = false;
                window.showToast?.('Manifest must be marked arrived before finalizing receipt.', 'warning');
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(config.finalize_endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notes: this.finalizeNotes || null,
                    }),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to finalize incoming receipt.');
                }

                this.manifestStatus = result?.data?.manifest?.status || 'received';
                this.showFinalizeModal = false;
                window.showToast?.(result.message || 'Incoming manifest finalized successfully.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to finalize incoming receipt.', 'error');
            } finally {
                this.loading = false;
            }
        },
    }));
}

if (window.Alpine) {
    registerWarehouseIncomingManifestShowPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseIncomingManifestShowPage);
}
