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
        openContainers: {},
        photoViewer: { open: false, row: null, index: 0 },
        receiveComplete: false,
        receiveScanMode: false,

        // Receive modal state
	        receiveModal: { open: false, itemId: null, itemIndex: -1 },
	        receiveDraft: {
	            description: '',
	            received_quantity: 0,
	            line_status: 'received',
	            notes: '',
	            scanned_label_barcode: null,
        },

        isFinalized() {
            return (this.manifestStatus || '').toLowerCase() === 'received';
        },

        canReceive() {
            return (this.manifestStatus || '').toLowerCase() === 'arrived';
        },

        receivedCount() {
            return this.items.filter((i) => i.received_at).length;
        },

        receivedQuantityTotal() {
            return this.items.reduce((sum, item) => sum + Number(item.received_quantity || 0), 0);
        },

        remainingCount() {
            return this.items.filter((i) => !i.received_at).length;
        },

        canFinalize() {
            return this.canReceive() && !this.isFinalized() && this.items.length > 0 && this.remainingCount() === 0;
        },

        discrepancyCount() {
            return this.items.filter(
                (i) => this.hasDiscrepancy(i),
            ).length;
        },

        discrepancyItems() {
            return this.items.filter((item) => this.hasDiscrepancy(item));
        },

        hasDiscrepancy(item) {
            return Boolean(!['pending', 'loaded', 'received'].includes(this.effectiveLineStatus(item)));
        },

        effectiveLineStatus(item) {
            if (!item) return 'pending';
            const status = String(item.line_status || 'pending').toLowerCase();
            const expected = Number(item.expected_quantity || 0);
            const received = Number(item.received_quantity || 0);

            if (status === 'damaged') return 'damaged';
            if (received < expected) return 'short';
            if (received > expected) return 'excess';

            return status;
        },

        physicalPackageTotal() {
            return this.items.reduce((sum, item) => sum + Number(item.physical_package_count || 0), 0);
        },

        itemQuantityTotal() {
            return this.items.reduce((sum, item) => sum + Number(item.expected_quantity || 0), 0);
        },

        init() {
            this.containerGroups().forEach((container) => {
                this.openContainers[container.id] = true;
            });

            const params = new URLSearchParams(window.location.search);
            const containerId = params.get('container');
            if (containerId) {
                this.openContainers[Number(containerId)] = true;
                this.$nextTick(() => {
                    const el = document.querySelector(`[data-incoming-container-id="${containerId}"]`);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        el.classList.add('ring-4', 'ring-orange-200');
                        window.setTimeout(() => el.classList.remove('ring-4', 'ring-orange-200'), 2600);
                    }
                });
            }

            const itemId = params.get('receive_item');
            if (itemId) {
                this.openReceiveModal(itemId, { scanMode: params.get('scan') === '1' });
            }
        },

        containerGroups() {
            const groups = new Map();

            this.items.forEach((item) => {
                const id = item.container_id || `unassigned-${item.container_code || 'none'}`;
                if (!groups.has(id)) {
                    groups.set(id, {
                        id,
                        code: item.container_code || 'Unassigned',
                        type: item.container_type || 'Container',
                        sequence: item.container_sequence || 999999,
                        lines: 0,
                        qty: 0,
                        receivedLines: 0,
                        receivedQty: 0,
                        issues: 0,
                        progress: 0,
                        items: [],
                    });
                }

                const group = groups.get(id);
                const expected = Number(item.expected_quantity || 0);
                const received = Number(item.received_quantity || 0);
                group.lines += 1;
                group.qty += expected;
                group.receivedQty += received;
                if (item.received_at) group.receivedLines += 1;
                if (this.hasDiscrepancy(item)) group.issues += 1;
                group.items.push(item);
            });

            return Array.from(groups.values())
                .sort((a, b) => Number(a.sequence) - Number(b.sequence))
                .map((group) => ({
                    ...group,
                    progress: group.lines > 0 ? Math.round((group.receivedLines / group.lines) * 100) : 0,
                }));
        },

        isContainerOpen(containerId) {
            return this.openContainers[containerId] !== false;
        },

        toggleContainer(containerId) {
            this.openContainers[containerId] = !this.isContainerOpen(containerId);
        },

        primaryPhoto(row) {
            return row?.photos?.primary?.[0] || null;
        },

        photoCount(row) {
            return Number(row?.photos?.primary?.length || 0);
        },

        openPhotoViewer(row) {
            if (!this.photoCount(row)) return;
            this.photoViewer = { open: true, row, index: 0 };
        },

        closePhotoViewer() {
            this.photoViewer = { open: false, row: null, index: 0 };
        },

        viewerPhotos() {
            return this.photoViewer.row?.photos?.primary || [];
        },

        currentViewerPhoto() {
            return this.viewerPhotos()[this.photoViewer.index] || null;
        },

        selectViewerPhoto(index) {
            const photos = this.viewerPhotos();
            if (!photos.length) return;
            this.photoViewer.index = Math.max(0, Math.min(Number(index || 0), photos.length - 1));
        },

        nextViewerPhoto() {
            const photos = this.viewerPhotos();
            if (photos.length < 2) return;
            this.photoViewer.index = (this.photoViewer.index + 1) % photos.length;
        },

        previousViewerPhoto() {
            const photos = this.viewerPhotos();
            if (photos.length < 2) return;
            this.photoViewer.index = (this.photoViewer.index - 1 + photos.length) % photos.length;
        },

        packageUnitLabel(count) {
            const total = Number(count || 0);
            return `${total} physical package${total === 1 ? '' : 's'}`;
        },

        itemUnitLabel(count) {
            const total = Number(count || 0);
            return `${total} item${total === 1 ? '' : 's'}`;
        },

        shortDateTime(value) {
            if (!value) return '-';
            return String(value).replace('T', ' ').replace('.000000Z', '').slice(0, 16);
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

        receiptStatusLabel(status) {
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

        discrepancyTone(statusOrItem) {
            const status = typeof statusOrItem === 'object'
                ? this.effectiveLineStatus(statusOrItem)
                : String(statusOrItem || '').toLowerCase();

            switch (status) {
                case 'short':
                    return 'border-amber-200 bg-amber-50 text-amber-800';
                case 'excess':
                    return 'border-indigo-200 bg-indigo-50 text-indigo-800';
                case 'damaged':
                    return 'border-rose-200 bg-rose-50 text-rose-800';
                default:
                    return 'border-slate-200 bg-slate-50 text-slate-700';
            }
        },

        discrepancyCopy(item) {
            const status = this.receiptStatusLabel(this.effectiveLineStatus(item));
            const expected = Number(item?.expected_quantity || 0);
            const received = Number(item?.received_quantity || 0);

            if (!this.hasDiscrepancy(item)) return '';

            return `${status}: received ${received} of ${expected}`;
        },

        receiveDraftRow() {
	            const row = this.items[this.receiveModal.itemIndex] || {};

	            return {
	                ...row,
	                description: this.receiveDraft.description ?? row.description ?? '',
	                received_quantity: Number(this.receiveDraft.received_quantity ?? 0),
	                line_status: this.receiveDraft.line_status || row.line_status || 'pending',
	                notes: this.receiveDraft.notes ?? '',
                scanned_label_barcode: this.receiveDraft.scanned_label_barcode || row.scanned_label_barcode || null,
            };
        },

        labelCodes(row) {
            const labels = Array.isArray(row?.labels) ? row.labels : [];
            if (!labels.length) return '';

            const first = labels[0]?.barcode_value || '';
            const match = String(first).match(/^(.*-)(\d+)$/);
            if (!match) {
                return labels.map((label) => label.barcode_value).filter(Boolean).join(', ');
            }

            const prefix = match[1];
            const suffixes = labels
                .map((label) => String(label.barcode_value || ''))
                .filter((code) => code.startsWith(prefix))
                .map((code) => code.slice(prefix.length))
                .filter(Boolean);

            if (suffixes.length !== labels.length) {
                return labels.map((label) => label.barcode_value).filter(Boolean).join(', ');
            }

            return `${prefix}${suffixes.join(',')}`;
        },

        openReceiveModal(itemId, options = {}) {
            if (this.isFinalized()) return;
            if (!this.canReceive()) {
                window.showToast?.('Manifest must be marked arrived before receiving.', 'warning');
                return;
            }
	            const idx = this.items.findIndex((i) => Number(i.shipment_item_id) === Number(itemId));
	            if (idx < 0) return;
	            const row = this.items[idx];
	            const isNewReceipt = !row.received_at;
	            this.receiveDraft = {
	                description: row.description || '',
	                received_quantity: Number(isNewReceipt ? row.expected_quantity || 0 : row.received_quantity || 0),
	                line_status: isNewReceipt ? 'received' : row.line_status || 'received',
	                notes: isNewReceipt ? '' : row.notes || '',
                scanned_label_barcode: row.scanned_label_barcode || null,
            };
            this.receiveComplete = false;
            this.receiveScanMode = Boolean(options.scanMode);
            this.receiveModal = { open: true, itemId, itemIndex: idx };
        },

        markExpected(itemId) {
            const row = this.items.find((item) => Number(item.shipment_item_id) === Number(itemId));
	            if (!row) return;
	            this.receiveDraft.received_quantity = Number(row.expected_quantity || 0);
	            this.receiveDraft.line_status = 'received';
	            this.receiveDraft.notes = '';
	            this.receiveDraft.description = row.description || '';
	            this.saveItem(itemId);
        },

        closeReceiveModal() {
	            this.receiveComplete = false;
	            this.receiveScanMode = false;
	            this.receiveModal = { open: false, itemId: null, itemIndex: -1 };
	            this.receiveDraft = {
	                description: '',
	                received_quantity: 0,
	                line_status: 'received',
	                notes: '',
                scanned_label_barcode: null,
            };
        },

        showScanNextAction() {
            return this.receiveScanMode;
        },

        scanNext() {
            window.location.href = config.index_url || '/warehouse/manifests/incoming';
        },

        viewManifest() {
            const row = this.items[this.receiveModal.itemIndex];
            if (!row?.manifest_url) return;
            const separator = row.manifest_url.includes('?') ? '&' : '?';
            window.location.href = `${row.manifest_url}${separator}receive_item=${row.shipment_item_id}&scan=1`;
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
            const draft = this.receiveDraftRow();

            if (this.hasDiscrepancy(draft) && !String(draft.notes || '').trim()) {
                window.showToast?.('Add discrepancy notes before saving this receiving line.', 'warning');
                return;
            }

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
	                    received_quantity: Number(draft.received_quantity ?? 0),
	                    line_status: draft.line_status || null,
	                    description: draft.description || null,
	                    notes: draft.notes || null,
	                        scanned_label_barcode: draft.scanned_label_barcode || null,
	                    }),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to save receiving line.');
                }

	                const updatedLine = result?.data?.line;
	                if (updatedLine) {
	                    row.received_quantity = Number(updatedLine.received_quantity ?? row.received_quantity ?? 0);
	                    row.description = updatedLine.shipment_item?.description ?? updatedLine.shipmentItem?.description ?? row.description;
	                    row.line_status = updatedLine.line_status || row.line_status;
                    row.notes = updatedLine.notes ?? row.notes;
                    row.received_at = updatedLine.received_at || row.received_at;
                }

                this.receiveComplete = true;
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

            if (this.remainingCount() > 0) {
                this.showFinalizeModal = false;
                window.showToast?.('Receive every manifest line before finalizing.', 'warning');
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
