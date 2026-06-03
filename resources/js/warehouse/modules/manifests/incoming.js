import { createRemoteTablePage } from '../shared/table-page.js';
import { parseJsonAttribute } from '../../core/config.js';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function getConfig() {
    const container = document.querySelector('[data-warehouse-incoming-manifests-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-incoming-manifests-config', null);
    if (!config) {
        console.error('Invalid warehouse incoming manifests config JSON');
    }

    return config;
}

function registerWarehouseIncomingManifestsPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.data_endpoint) return;

    const statuses = [
        { value: 'in_transit', label: 'In Transit' },
        { value: 'arrived', label: 'Arrived' },
        { value: 'receiving', label: 'Receiving' },
        { value: 'received', label: 'Received' },
        { value: 'pending_receipt', label: 'Pending Receipt' },
        { value: 'cancelled', label: 'Cancelled' },
    ];

    const pageConfig = {
        endpoint: config.data_endpoint,
        defaultSort: 'created_at',
        defaultPerPage: 25,
        exportFileName: 'incoming-transfers',
        printTitle: 'Incoming Transfers',
        statuses,
        columns: [
            { key: 'manifest_number', label: 'Manifest', exportLabel: 'Manifest Number' },
            { key: 'origin_warehouse', label: 'Origin', exportLabel: 'Origin', sortKey: 'origin_warehouse_id' },
            { key: 'status', label: 'Status' },
            { key: 'driver_name', label: 'Driver', exportLabel: 'Driver', sortable: false },
            { key: 'items_count', label: 'Items', exportLabel: 'Items' },
            { key: 'received_count', label: 'Received', exportLabel: 'Received', sortable: false },
            { key: 'arrived_at', label: 'Arrived At', exportLabel: 'Arrived At' },
            { key: 'received_at', label: 'Received At', exportLabel: 'Received At' },
            { key: 'dispatched_at', label: 'Dispatched At', exportLabel: 'Dispatched At', visible: false },
            { key: 'assigned_at', label: 'Assigned At', exportLabel: 'Assigned At', visible: false },
            { key: 'created_at', label: 'Created At', exportLabel: 'Created At', visible: false },
            { key: 'destination_warehouse', label: 'Destination', exportLabel: 'Destination', visible: false },
            { key: 'actions', label: 'Actions', sortable: false },
        ],
    };

    Alpine.data('warehouseIncomingManifestsPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,
            showFilters: false,
            originWarehouses: Array.isArray(config.origin_warehouses) ? config.origin_warehouses : [],
            transportDrivers: Array.isArray(config.transport_drivers) ? config.transport_drivers : [],
            summary: { total: 0, in_transit: 0, arrived: 0, receiving: 0, received: 0, pending_receipt: 0 },
            scannerCode: '',
            scannerLoading: false,
            scanModalOpen: false,
            scannerActive: false,
            scannerStatus: '',
            scanModalMessage: '',
            scannerStream: null,
            scannerControls: null,
            scannerReader: null,
            scannerDetector: null,
            scannerFrame: null,
            scannerMisses: 0,
            scannerPending: false,
            scannerRejectedCodes: {},
            items: [],
            loading: false,
            receiveComplete: false,
            receiveScanMode: true,
	            receiveModal: { open: false, itemId: null, itemIndex: -1 },
	            receiveDraft: {
	                description: '',
	                received_quantity: 0,
	                line_status: 'received',
	                notes: '',
	                scanned_label_barcode: null,
            },
            statCards: [
                { key: 'total', label: 'Total', icon: 'truck', iconClass: 'bg-slate-100 text-slate-700 ring-slate-200' },
                { key: 'in_transit', label: 'In Transit', icon: 'road', iconClass: 'bg-violet-50 text-violet-700 ring-violet-200' },
                { key: 'arrived', label: 'Arrived', icon: 'pin', iconClass: 'bg-amber-50 text-amber-700 ring-amber-200' },
                { key: 'receiving', label: 'Receiving', icon: 'box', iconClass: 'bg-cyan-50 text-cyan-700 ring-cyan-200' },
                { key: 'received', label: 'Received', icon: 'check', iconClass: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
                { key: 'pending_receipt', label: 'Pending Receipt', icon: 'alert', iconClass: 'bg-orange-50 text-orange-700 ring-orange-200' },
            ],
            filters: {
                status: '',
                origin_warehouse_id: '',
                driver_id: '',
                date_type: 'created_at',
                date_from: '',
                date_to: '',
            },
            dateRangePicker: null,

            init() {
                this.loadData();
                this.initDateRange();
                if (new URLSearchParams(window.location.search).get('scanner') === '1') {
                    this.$nextTick(() => this.openScanModal());
                }
            },

            isFinalized() {
                const row = this.items[this.receiveModal.itemIndex];
                return (row?.manifest_status || '').toLowerCase() === 'received';
            },

            canReceive() {
                const row = this.items[this.receiveModal.itemIndex];
                return (row?.manifest_status || '').toLowerCase() === 'arrived';
            },

            showScanNextAction() {
                return true;
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

            hasDiscrepancy(item) {
                return Boolean(!['pending', 'loaded', 'received'].includes(this.effectiveLineStatus(item)));
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

            async scanIncomingPackage() {
                const code = String(this.scannerCode || '').trim();
                if (!code) {
                    window.showToast?.('Scan or enter a package label first.', 'warning');
                    this.$refs.scannerInput?.focus();
                    return;
                }

                this.scannerLoading = true;
                try {
                    const response = await fetch(config.scan_endpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ barcode: code }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Package was not found in incoming manifests.');
                    }

                    if (result?.data?.type === 'container') {
                        const redirectUrl = result?.data?.container?.redirect_url;
                        if (redirectUrl) {
                            this.closeScanModal();
                            window.location.href = redirectUrl;
                            return;
                        }
                        throw new Error('Container was found, but the manifest link could not be opened.');
                    }

                    const item = result?.data?.item;
                    if (!item) throw new Error('Package details could not be loaded.');

                    await this.openScannedIncomingItem(item);
                    window.showToast?.(result.message || 'Package found.', item.manifest_status === 'arrived' ? 'success' : 'warning');
                } catch (error) {
                    console.error(error);
                    this.scanModalMessage = error.message || 'Unable to scan incoming package.';
                    window.showToast?.(this.scanModalMessage, 'error');
                } finally {
                    this.scannerLoading = false;
                }
            },

            async openScannedIncomingItem(item) {
                this.closeScanModal();
                this.scannerCode = '';
                this.items = [item];
                await this.loadData();
                this.openReceiveModal(item.shipment_item_id, { scanMode: true });
            },

            openReceiveModal(itemId, options = {}) {
                const idx = this.items.findIndex((item) => Number(item.shipment_item_id) === Number(itemId));
                if (idx < 0) return;
	                const row = this.items[idx];
	                const isNewReceipt = !row.received_at;
	                const scannedQuantity = Number(row.received_quantity || 1);
	                this.receiveDraft = {
	                    description: row.description || '',
	                    received_quantity: Number(row.scan_mode === 'label' ? scannedQuantity : (isNewReceipt ? row.expected_quantity || 0 : row.received_quantity || 0)),
	                    line_status: isNewReceipt || row.scan_mode === 'label' ? 'received' : row.line_status || 'received',
	                    notes: isNewReceipt || row.scan_mode === 'label' ? '' : row.notes || '',
	                    scanned_label_barcode: row.scanned_label_barcode || null,
                };
                this.receiveComplete = false;
                this.receiveScanMode = Boolean(options.scanMode ?? true);
                this.receiveModal = { open: true, itemId, itemIndex: idx };
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

            closeReceiveModal() {
	                this.receiveComplete = false;
	                this.receiveScanMode = true;
	                this.receiveModal = { open: false, itemId: null, itemIndex: -1 };
	                this.receiveDraft = {
	                    description: '',
	                    received_quantity: 0,
	                    line_status: 'received',
	                    notes: '',
	                    scanned_label_barcode: null,
                };
            },

            scanNext() {
                this.closeReceiveModal();
                this.openScanModal();
            },

            viewManifest() {
                const row = this.items[this.receiveModal.itemIndex];
                if (!row?.manifest_url) return;
                const separator = row.manifest_url.includes('?') ? '&' : '?';
                window.location.href = `${row.manifest_url}${separator}receive_item=${row.shipment_item_id}&scan=1`;
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
                if (!row?.receive_endpoint) return;
                const draft = this.receiveDraftRow();

                if (this.hasDiscrepancy(draft) && !String(draft.notes || '').trim()) {
                    window.showToast?.('Add discrepancy notes before saving this receiving line.', 'warning');
                    return;
                }

                this.loading = true;
                try {
                    const response = await fetch(row.receive_endpoint, {
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
                    this.loadData();
                    window.showToast?.(result.message || 'Line saved successfully.', 'success');
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to save receiving line.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            openScanModal() {
                this.scannerCode = '';
                this.scannerStatus = '';
                this.scanModalMessage = '';
                this.scannerPending = false;
                this.scannerRejectedCodes = {};
                this.scanModalOpen = true;
                this.$nextTick(() => this.$refs.scannerInput?.focus());
            },

            closeScanModal() {
                this.stopScanner();
                this.scanModalOpen = false;
            },

            async startScanner() {
                if (window.ZXingBrowser?.BrowserMultiFormatReader || 'BarcodeDetector' in window) {
                    await this.startZxingScanner();
                    return;
                }

                this.scannerStatus = 'Camera scanning is not supported in this browser. Use manual entry below.';
                this.$nextTick(() => this.$refs.scannerInput?.focus());
            },

            async startZxingScanner() {
                if (!navigator.mediaDevices?.getUserMedia) {
                    this.scannerStatus = 'Camera access is not available. Use manual entry below.';
                    this.$nextTick(() => this.$refs.scannerInput?.focus());
                    return;
                }

                try {
                    this.stopScanner();
                    this.scannerStatus = 'Starting camera...';
                    this.scanModalMessage = '';
                    const video = this.$refs.scanVideo;
                    video.classList.remove('hidden');
                    this.scannerActive = true;

                    if (window.ZXingBrowser?.BrowserMultiFormatReader) {
                        const hints = new Map();
                        hints.set(window.ZXingBrowser.DecodeHintType.POSSIBLE_FORMATS, this.zxingBarcodeFormats());
                        hints.set(window.ZXingBrowser.DecodeHintType.TRY_HARDER, true);
                        hints.set(window.ZXingBrowser.DecodeHintType.ENABLE_CODE_39_EXTENDED_MODE, true);

                        this.scannerReader = new window.ZXingBrowser.BrowserMultiFormatReader(hints, {
                            delayBetweenScanAttempts: 80,
                            delayBetweenScanSuccess: 500,
                        });
                    }

                    if ('BarcodeDetector' in window) {
                        this.scannerDetector = await this.createBarcodeDetector();
                    }

                    this.scannerStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: 'environment' },
                            width: { ideal: 1920 },
                            height: { ideal: 1080 },
                            advanced: [{ focusMode: 'continuous' }],
                        },
                    });
                    video.srcObject = this.scannerStream;
                    video.setAttribute('playsinline', 'true');
                    video.muted = true;
                    await video.play();

                    this.scannerMisses = 0;
                    this.scannerStatus = 'Scanning barcode...';
                    this.scannerFrame = requestAnimationFrame(() => this.scanZxingFrame());
                } catch (error) {
                    console.error('Incoming scanner failed', error);
                    this.scannerStatus = 'Camera scanner could not start. Use manual entry below.';
                    this.stopScanner();
                    this.$nextTick(() => this.$refs.scannerInput?.focus());
                }
            },

            zxingBarcodeFormats() {
                const formats = window.ZXingBrowser?.BarcodeFormat || {};
                return [
                    formats.CODE_128,
                    formats.CODE_39,
                    formats.CODE_93,
                    formats.CODABAR,
                    formats.ITF,
                    formats.EAN_13,
                    formats.EAN_8,
                    formats.UPC_A,
                    formats.UPC_E,
                    formats.RSS_14,
                    formats.RSS_EXPANDED,
                    formats.PDF_417,
                    formats.QR_CODE,
                    formats.DATA_MATRIX,
                    formats.AZTEC,
                ].filter((format) => format !== undefined && format !== null);
            },

            async createBarcodeDetector() {
                if (!('BarcodeDetector' in window)) return null;

                const desiredFormats = ['code_128', 'code_39', 'code_93', 'codabar', 'itf', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code', 'pdf417', 'data_matrix', 'aztec'];
                let formats = desiredFormats;

                try {
                    if (BarcodeDetector.getSupportedFormats) {
                        const supported = await BarcodeDetector.getSupportedFormats();
                        formats = desiredFormats.filter((format) => supported.includes(format));
                    }
                } catch (error) {
                    formats = desiredFormats;
                }

                try {
                    return new BarcodeDetector({ formats });
                } catch (error) {
                    try {
                        return new BarcodeDetector();
                    } catch (innerError) {
                        return null;
                    }
                }
            },

            async scanZxingFrame() {
                if (!this.scannerActive) return;
                if (this.scannerPending) {
                    this.scannerFrame = requestAnimationFrame(() => this.scanZxingFrame());
                    return;
                }

                const video = this.$refs.scanVideo;
                const canvas = this.$refs.scanCanvas;
                const reader = this.scannerReader;
                const detector = this.scannerDetector;

                if (!video || !canvas || (!reader && !detector) || video.readyState < 2 || !video.videoWidth || !video.videoHeight) {
                    this.scannerFrame = requestAnimationFrame(() => this.scanZxingFrame());
                    return;
                }

                const videoWidth = video.videoWidth;
                const videoHeight = video.videoHeight;
                const context = canvas.getContext('2d', { willReadFrequently: true });
                const crops = [
                    { width: 1, height: 1, y: 0.50 },
                    { width: 0.92, height: 0.42, y: 0.50 },
                    { width: 0.96, height: 0.30, y: 0.50 },
                    { width: 0.92, height: 0.46, y: 0.45 },
                ];

                for (const crop of crops) {
                    const cropWidth = Math.floor(videoWidth * crop.width);
                    const cropHeight = Math.floor(videoHeight * crop.height);
                    const sourceX = Math.max(0, Math.floor((videoWidth - cropWidth) / 2));
                    const centerY = Math.floor(videoHeight * crop.y);
                    const sourceY = Math.max(0, Math.min(videoHeight - cropHeight, Math.floor(centerY - (cropHeight / 2))));

                    canvas.width = cropWidth;
                    canvas.height = cropHeight;
                    context.drawImage(video, sourceX, sourceY, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);

                    const nativeText = await this.detectWithNativeBarcodeDetector(canvas);
                    if (nativeText && await this.submitDetectedScanCode(nativeText)) return;

                    const zxingText = this.detectWithZxing(canvas);
                    if (zxingText && await this.submitDetectedScanCode(zxingText)) return;
                }

                this.scannerMisses += 1;
                if (this.scannerMisses === 90) {
                    this.scannerStatus = 'Move closer and keep the bars sharp.';
                } else if (this.scannerMisses === 180) {
                    this.scannerStatus = 'Still scanning. Try manual entry if it does not pick.';
                }

                this.scannerFrame = requestAnimationFrame(() => this.scanZxingFrame());
            },

            async submitDetectedScanCode(rawValue) {
                const code = this.normalizedScanCode(rawValue);
                if (!code) return false;

                const lastRejectedAt = this.scannerRejectedCodes[code] || 0;
                if (Date.now() - lastRejectedAt < 4000) return false;

                this.scannerPending = true;
                this.scannerCode = code;
                this.scannerStatus = `Checking ${code}...`;
                this.scanModalMessage = '';

                try {
                    const response = await fetch(config.scan_endpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ barcode: code }),
                    });
                    const result = await response.json();
                    this.scannerPending = false;

                    if (!this.scannerActive) return false;

                    if (!response.ok || !result.success) {
                        this.scannerRejectedCodes[code] = Date.now();
                        this.scanModalMessage = `${result.message || 'No package found for this code.'} Last read: ${code}. Still scanning...`;
                        this.scannerStatus = 'Scanning for another barcode...';
                        return false;
                    }

                    if (result?.data?.type === 'container') {
                        const redirectUrl = result?.data?.container?.redirect_url;
                        if (redirectUrl) {
                            this.closeScanModal();
                            window.location.href = redirectUrl;
                            return true;
                        }
                    }

                    await this.openScannedIncomingItem(result.data.item);
                    return true;
                } catch (error) {
                    this.scannerPending = false;
                    this.scannerRejectedCodes[code] = Date.now();
                    this.scanModalMessage = `Unable to check ${code}. Still scanning...`;
                    this.scannerStatus = 'Scanning for another barcode...';
                    return false;
                }
            },

            normalizedScanCode(rawValue) {
                return String(rawValue || '').trim().toUpperCase().replace(/\s+/g, '');
            },

            async detectWithNativeBarcodeDetector(canvas) {
                if (!this.scannerDetector) return '';

                try {
                    const codes = await this.scannerDetector.detect(canvas);
                    return codes?.[0]?.rawValue || '';
                } catch (error) {
                    return '';
                }
            },

            detectWithZxing(canvas) {
                if (!this.scannerReader) return '';

                try {
                    const result = this.scannerReader.decodeFromCanvas(canvas);
                    return result?.getText?.() || result?.text || '';
                } catch (error) {
                    return '';
                }
            },

            stopScanner() {
                if (this.scannerControls) {
                    this.scannerControls.stop?.();
                    this.scannerControls = null;
                }
                if (this.scannerReader) {
                    this.scannerReader.reset?.();
                    this.scannerReader = null;
                }
                this.scannerDetector = null;
                if (this.scannerFrame) {
                    cancelAnimationFrame(this.scannerFrame);
                    this.scannerFrame = null;
                }
                if (this.scannerStream) {
                    this.scannerStream.getTracks().forEach((track) => track.stop());
                    this.scannerStream = null;
                }
                if (this.$refs.scanVideo) {
                    this.$refs.scanVideo.pause?.();
                    this.$refs.scanVideo.srcObject = null;
                    this.$refs.scanVideo.classList.add('hidden');
                }
                this.scannerMisses = 0;
                this.scannerPending = false;
                this.scannerActive = false;
            },

            isSortable(key) {
                const column = this.columns.find((item) => item.key === key);
                return Boolean(column && column.sortable !== false);
            },

            isSortedColumn(key) {
                const column = this.columns.find((item) => item.key === key);
                return this.sortBy === (column?.sortKey || key);
            },

            sort(column) {
                if (!this.isSortable(column)) return;

                const sortKey = this.columns.find((item) => item.key === column)?.sortKey || column;
                if (this.sortBy === sortKey) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortBy = sortKey;
                    this.sortDirection = 'asc';
                }

                this.meta.current_page = 1;
                this.loadData();
            },

            buildParams(overrides = {}) {
                const params = page.buildParams.call(this, overrides);
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        params.set(key, value);
                    }
                });
                params.delete('statusFilter');
                return params;
            },

            async loadData() {
                this.loading = true;
                try {
                    const response = await fetch(`${this.endpoint}?${this.buildParams().toString()}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Failed to fetch incoming transfers.');
                    this.rows = Array.isArray(result.data) ? result.data : [];
                    this.meta = result.meta || this.meta;
                    this.summary = result.summary || this.summary;
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to load incoming transfers.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            applyFilters() {
                this.meta.current_page = 1;
                this.loadData();
            },

            resetFiltersOnly() {
                Object.keys(this.filters).forEach((key) => {
                    this.filters[key] = key === 'date_type' ? 'created_at' : '';
                });
                this.search = '';
                if (this.$refs.dateRange) this.$refs.dateRange.value = '';
            },

            clearFilters() {
                this.resetFiltersOnly();
                this.meta.current_page = 1;
                this.loadData();
            },

            applySummaryFilter(key) {
                this.resetFiltersOnly();
                this.meta.current_page = 1;

                if (key !== 'total') {
                    this.filters.status = key;
                }

                this.loadData();
            },

            clearFilter(key) {
                if (!Object.prototype.hasOwnProperty.call(this.filters, key)) return;

                this.filters[key] = key === 'date_type' ? 'created_at' : '';
                if ((key === 'date_from' || key === 'date_to') && this.$refs.dateRange) {
                    this.$refs.dateRange.value = '';
                    this.filters.date_from = '';
                    this.filters.date_to = '';
                }
                this.applyFilters();
            },

            activeFilterChips() {
                const labels = {
                    status: 'Status',
                    origin_warehouse_id: 'Origin',
                    driver_id: 'Driver',
                    date_from: 'Date from',
                    date_to: 'Date to',
                };

                return Object.entries(this.filters)
                    .filter(([key, value]) => key !== 'date_type' && value !== '' && value !== null && value !== undefined)
                    .map(([key, value]) => ({ key, label: `${labels[key] || key}: ${this.filterValueLabel(key, value)}` }));
            },

            filterValueLabel(key, value) {
                if (key === 'status') return this.statuses.find((item) => item.value === value)?.label || value;
                if (key === 'origin_warehouse_id') return this.originWarehouses.find((item) => String(item.id) === String(value))?.name || value;
                if (key === 'driver_id') return this.transportDrivers.find((item) => String(item.id) === String(value))?.name || value;
                return String(value).replace(/_/g, ' ');
            },

            tableHeaderClass(key) {
                if (['status', 'items_count', 'received_count'].includes(key)) return 'text-center';
                if (key === 'actions') return 'text-right';
                return 'text-left';
            },

            tableHeaderContentClass(key) {
                if (['status', 'items_count', 'received_count'].includes(key)) return 'justify-center';
                if (key === 'actions') return 'justify-end';
                return '';
            },

            statusLabel(status) {
                return this.statuses.find((item) => item.value === status)?.label || String(status || '').replace(/_/g, ' ');
            },

            statusBadgeClass(status, label = '') {
                const value = (label || status || '').toLowerCase().replace(/\s+/g, '_');
                switch (value) {
                    case 'in_transit':
                        return 'border-violet-200 bg-violet-50 text-violet-700';
                    case 'arrived':
                        return 'border-amber-200 bg-amber-50 text-amber-700';
                    case 'receiving':
                        return 'border-cyan-200 bg-cyan-50 text-cyan-700';
                    case 'received':
                        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                    case 'pending_receipt':
                        return 'border-orange-200 bg-orange-50 text-orange-700';
                    case 'cancelled':
                        return 'border-rose-200 bg-rose-50 text-rose-700';
                    case 'assigned':
                        return 'border-blue-200 bg-blue-50 text-blue-700';
                    case 'loading':
                        return 'border-indigo-200 bg-indigo-50 text-indigo-700';
                    default:
                        return 'border-slate-200 bg-slate-50 text-slate-700';
                }
            },

            formatDisplayDate(value) {
                if (!value) return '-';

                const normalized = String(value).replace(' ', 'T');
                const date = new Date(normalized);
                if (Number.isNaN(date.getTime())) return value;

                return new Intl.DateTimeFormat('en-GH', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true,
                }).format(date);
            },

            initDateRange() {
                if (!this.$refs.dateRange) return;

                const setupPicker = () => {
                    if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;

                    const $input = window.$(this.$refs.dateRange);

                    $input.daterangepicker({
                        autoUpdateInput: false,
                        alwaysShowCalendars: true,
                        opens: 'left',
                        locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
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
                        this.filters.date_from = picker.startDate.format('YYYY-MM-DD');
                        this.filters.date_to = picker.endDate.format('YYYY-MM-DD');
                        $input.val(`${this.filters.date_from} - ${this.filters.date_to}`);
                        this.applyFilters();
                    });

                    $input.on('cancel.daterangepicker', () => {
                        this.filters.date_from = '';
                        this.filters.date_to = '';
                        $input.val('');
                        this.applyFilters();
                    });

                    this.dateRangePicker = $input.data('daterangepicker');
                };

                if (window.$ && window.moment && window.$.fn.daterangepicker) {
                    setupPicker();
                    return;
                }

                const cssId = 'daterangepicker-css';
                if (!document.getElementById(cssId)) {
                    const link = document.createElement('link');
                    link.id = cssId;
                    link.rel = 'stylesheet';
                    link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                    document.head.appendChild(link);
                }

                const loadScript = (id, src) => new Promise((resolve) => {
                    if (document.getElementById(id)) return resolve();
                    const script = document.createElement('script');
                    script.id = id;
                    script.src = src;
                    script.onload = () => resolve();
                    document.body.appendChild(script);
                });

                loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                    .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                    .then(() => {
                        window.$ = window.jQuery = window.jQuery || window.$;
                        window.moment = window.moment || moment;
                        return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
                    })
                    .then(setupPicker);
            },
        };
    });
}

if (window.Alpine) {
    registerWarehouseIncomingManifestsPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseIncomingManifestsPage);
}
