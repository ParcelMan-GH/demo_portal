import { createRemoteTablePage } from '../shared/table-page.js';
import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-packages-config]');
    if (!container) return null;
    return parseJsonAttribute(container, 'data-warehouse-packages-config', null);
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function withItem(urlTemplate, itemId) {
    return (urlTemplate || '').replace('__ITEM__', String(itemId));
}

function registerWarehousePackagesPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.endpoint) return;

    const pageConfig = {
        endpoint: config.endpoint,
        defaultSort: 'received_at',
        defaultPerPage: 25,
        exportFileName: 'warehouse-packages',
        printTitle: 'Warehouse Packages',
        columns: [
            { key: 'package', label: 'Package', sortable: false },
            { key: 'qty', label: 'Qty', sortable: false },
            { key: 'shipment', label: 'Shipment', sortable: false },
            { key: 'recipient', label: 'Recipient', sortable: false },
            { key: 'destination', label: 'Destination', sortable: false },
            { key: 'stage', label: 'Stage', sortable: false },
            { key: 'custody', label: 'Custody', sortable: false },
            { key: 'sort_batch', label: 'Sort Batch', sortable: false },
            { key: 'manifest', label: 'Manifest', sortable: false },
            { key: 'delivery', label: 'Delivery', sortable: false },
            { key: 'payment', label: 'Payment', sortable: false },
            { key: 'received', label: 'Received', sortable: false },
            { key: 'actions', label: 'Actions', sortable: false },
        ],
    };

    window.Alpine.data('warehousePackagesPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,
            showFilters: false,
            summary: { total: 0, at_warehouse: 0, in_transit: 0, out_for_delivery: 0, delivered: 0, payment_due: 0, total_paid: 0 },
            statCards: [
                { key: 'total', label: 'Total', icon: 'package', iconClass: 'bg-slate-100 text-slate-700 ring-slate-200' },
                { key: 'at_warehouse', label: 'At Warehouse', icon: 'warehouse', iconClass: 'bg-orange-50 text-orange-700 ring-orange-200' },
                { key: 'in_transit', label: 'In Transit', icon: 'truck', iconClass: 'bg-blue-50 text-blue-700 ring-blue-200' },
                { key: 'out_for_delivery', label: 'Out for Delivery', icon: 'route', iconClass: 'bg-violet-50 text-violet-700 ring-violet-200' },
                { key: 'delivered', label: 'Delivered', icon: 'check', iconClass: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
                { key: 'payment_due', label: 'Payment Due', icon: 'cedi', iconClass: 'bg-amber-50 text-amber-700 ring-amber-200' },
                { key: 'total_paid', label: 'Total Paid', icon: 'cedi', iconClass: 'bg-emerald-50 text-emerald-700 ring-emerald-200', money: true },
            ],
            filters: {
                date_from: '',
                date_to: '',
                delivered_date_from: '',
                delivered_date_to: '',
                status: '',
                custody: '',
                sort_batch: '',
                sort_batch_id: '',
                manifest_status: '',
                delivery_status: '',
                delivery_method: '',
                payment_status: '',
                delivery_staff_id: '',
                payment_staff_id: '',
                amount_min: '',
                amount_max: '',
                vendor: '',
            },
            statuses: Array.isArray(config.statuses) ? config.statuses : [],
            manifestStatuses: Array.isArray(config.manifest_statuses) ? config.manifest_statuses : [],
            openBatches: Array.isArray(config.open_batches) ? config.open_batches : [],
            warehouseUsers: Array.isArray(config.warehouse_users) ? config.warehouse_users : [],
            transferWarehouses: Array.isArray(config.transfer_warehouses) ? config.transfer_warehouses : [],
            editModalOpen: false,
            printModalOpen: false,
            modalLoading: false,
            printLoading: false,
            activeRow: null,
            editForm: {},
            printForm: {
                label_count: 1,
            },
            photoUploadFiles: [],
            removePhotoIds: [],
            photoPreviewOpen: false,
            photoPreviewPackage: null,
            photoPreviewUrls: [],
            activePhotoIndex: 0,

            init() {
                this.loadData();
                this.initDateRange();
            },

            buildParams(overrides = {}) {
                const params = page.buildParams.call(this, overrides);
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) {
                        params.set(key, value);
                    }
                });
                return params;
            },

            async loadData() {
                this.loading = true;
                try {
                    const response = await fetch(`${this.endpoint}?${this.buildParams().toString()}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Failed to fetch packages.');
                    this.rows = Array.isArray(result.data) ? result.data : [];
                    this.meta = result.meta || this.meta;
                    this.summary = result.summary || this.summary;
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to load packages.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            applyFilters() {
                this.meta.current_page = 1;
                this.loadData();
            },

            clearFilters() {
                Object.keys(this.filters).forEach((key) => { this.filters[key] = ''; });
                if (this.$refs.dateRange) this.$refs.dateRange.value = '';
                if (this.$refs.deliveredDateRange) this.$refs.deliveredDateRange.value = '';
                this.meta.current_page = 1;
                this.loadData();
            },

            resetFiltersOnly() {
                Object.keys(this.filters).forEach((key) => { this.filters[key] = ''; });
                if (this.$refs.dateRange) this.$refs.dateRange.value = '';
                if (this.$refs.deliveredDateRange) this.$refs.deliveredDateRange.value = '';
                this.search = '';
            },

            applySummaryFilter(key) {
                this.resetFiltersOnly();
                this.meta.current_page = 1;

                switch (key) {
                    case 'at_warehouse':
                        this.filters.custody = 'at_warehouse';
                        break;
                    case 'in_transit':
                        this.filters.status = 'in_transit';
                        break;
                    case 'out_for_delivery':
                        this.filters.status = 'out_for_delivery';
                        break;
                    case 'delivered':
                        this.filters.status = 'delivered';
                        break;
                    case 'payment_due':
                        this.filters.payment_status = 'due';
                        break;
                    case 'total_paid':
                        this.filters.payment_status = 'paid';
                        break;
                    case 'total':
                    default:
                        break;
                }

                this.loadData();
            },

            clearFilter(key) {
                if (Object.prototype.hasOwnProperty.call(this.filters, key)) {
                    this.filters[key] = '';
                    if ((key === 'date_from' || key === 'date_to') && this.$refs.dateRange) this.$refs.dateRange.value = '';
                    if ((key === 'delivered_date_from' || key === 'delivered_date_to') && this.$refs.deliveredDateRange) this.$refs.deliveredDateRange.value = '';
                    this.applyFilters();
                }
            },

            activeFilterChips() {
                const labels = {
                    date_from: 'Date from',
                    date_to: 'Date to',
                    delivered_date_from: 'Delivered from',
                    delivered_date_to: 'Delivered to',
                    status: 'Status',
                    custody: 'Custody',
                    sort_batch: 'Sort batch',
                    sort_batch_id: 'Batch',
                    manifest_status: 'Manifest',
                    delivery_status: 'Delivery',
                    delivery_method: 'Method',
                    payment_status: 'Payment',
                    delivery_staff_id: 'Delivery staff',
                    payment_staff_id: 'Payment staff',
                    amount_min: 'Min fee',
                    amount_max: 'Max fee',
                    vendor: 'Vendor',
                };
                return Object.entries(this.filters)
                    .filter(([, value]) => value !== '' && value !== null && value !== undefined)
                    .map(([key, value]) => ({ key, label: `${labels[key] || key}: ${this.filterValueLabel(key, value)}` }));
            },

            filterValueLabel(key, value) {
                if (key === 'status') return this.statuses.find((item) => item.value === value)?.label || value;
                if (key === 'manifest_status') return this.manifestStatuses.find((item) => item.value === value)?.label || value;
                if (key === 'sort_batch_id') return this.openBatches.find((item) => String(item.id) === String(value))?.batch_number || value;
                if (key === 'delivery_staff_id' || key === 'payment_staff_id') return this.warehouseUsers.find((item) => String(item.id) === String(value))?.name || value;
                return String(value).replace(/_/g, ' ');
            },

            openEditModal(row) {
                this.activeRow = row;
                this.editForm = {
                    description: row.item_description || '',
                    quantity: row.quantity || row.received_quantity || 1,
                    delivery_recipient_name: row.recipient_name || '',
                    delivery_recipient_phone: row.recipient_phone || '',
                    delivery_region_id: row.delivery_location?.region_id || '',
                    delivery_district_id: row.delivery_location?.district_id || '',
                    delivery_town: row.delivery_location?.town || row.destination?.split(',')[0] || '',
                    locationQuery: row.delivery_location?.display || row.destination || '',
                    locationResults: [],
                    selectedLocation: null,
                    _showDropdown: false,
                    delivery_landmark: '',
                    delivery_instructions: '',
                    delivery_method: row.delivery_method || 'direct',
                    forward_to_warehouse_id: row.forward_to_warehouse_id || '',
                };
                this.photoUploadFiles = [];
                this.removePhotoIds = [];
                this.editModalOpen = true;
            },

            closeEditModal() {
                if (this.modalLoading) return;
                this.editModalOpen = false;
                this.activeRow = null;
                this.editForm = {};
                this.photoUploadFiles = [];
                this.removePhotoIds = [];
            },

            canSaveEditPackage() {
                return Boolean(
                    !this.modalLoading
                    && (this.editForm.description || '').trim()
                    && Number(this.editForm.quantity || 0) > 0
                    && this.hasRequiredPackagePhotos()
                );
            },

            async savePackage() {
                if (!this.activeRow || !this.canSaveEditPackage()) return;
                this.modalLoading = true;
                try {
                    const formData = new FormData();
                    formData.append('_method', 'PUT');
                    formData.append('description', this.editForm.description || '');
                    formData.append('quantity', String(this.editForm.quantity || 1));
                    formData.append('delivery_recipient_name', this.editForm.delivery_recipient_name || '');
                    formData.append('delivery_recipient_phone', this.editForm.delivery_recipient_phone || '');
                    formData.append('delivery_region_id', this.editForm.delivery_region_id || '');
                    formData.append('delivery_district_id', this.editForm.delivery_district_id || '');
                    formData.append('delivery_town', this.editForm.delivery_town || this.editForm.locationQuery || '');
                    formData.append('delivery_landmark', this.editForm.delivery_landmark || '');
                    formData.append('delivery_instructions', this.editForm.delivery_instructions || '');
                    formData.append('delivery_method', this.editForm.delivery_method || 'direct');
                    formData.append('forward_to_warehouse_id', this.editForm.forward_to_warehouse_id || '');
                    this.photoUploadFiles.forEach((file) => formData.append('photos[]', file));
                    this.removePhotoIds.forEach((id) => formData.append('remove_photo_ids[]', id));

                    const response = await fetch(withItem(config.update_url, this.activeRow.id), {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: formData,
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Unable to update package.');
                    this.replaceRow(result.data);
                    this.editModalOpen = false;
                    this.activeRow = null;
                    this.editForm = {};
                    this.photoUploadFiles = [];
                    this.removePhotoIds = [];
                    window.showToast?.(result.message || 'Package updated.', 'success');
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to update package.', 'error');
                } finally {
                    this.modalLoading = false;
                }
            },

            searchLocation(target) {
                const query = (target.locationQuery || '').trim();
                target.selectedLocation = null;
                target.delivery_region_id = '';
                target.delivery_district_id = '';
                target.delivery_town = query;
                if (query.length < 2) {
                    target.locationResults = [];
                    target._showDropdown = false;
                    return;
                }
                clearTimeout(target._timeout);
                target._searchSeq = (target._searchSeq || 0) + 1;
                const searchSeq = target._searchSeq;
                target._timeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`${config.location_search_url}?q=${encodeURIComponent(query)}`, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const result = await response.json();
                        if (searchSeq !== target._searchSeq) return;
                        target.locationResults = result.locations || [];
                        target._showDropdown = target.locationResults.length > 0;
                    } catch {
                        target.locationResults = [];
                    }
                }, 250);
            },

            selectLocation(target, location) {
                clearTimeout(target._timeout);
                target._searchSeq = (target._searchSeq || 0) + 1;
                target.selectedLocation = location;
                target.locationQuery = location.display;
                target.locationResults = [];
                target._showDropdown = false;
                target.delivery_region_id = location.region?.id || '';
                target.delivery_district_id = location.district?.id || '';
                target.delivery_town = location.name || location.display;
            },

            closeLocationDropdownSoon(target) {
                setTimeout(() => { target._showDropdown = false; }, 120);
            },

            handlePackagePhotos(event) {
                this.photoUploadFiles = Array.from(event.target.files || []);
            },

            rowPhotoList(row = this.activeRow) {
                const photos = row?.photos || {};
                const list = Array.isArray(photos.primary) ? photos.primary : [];
                return list.filter((photo) => !this.removePhotoIds.includes(Number(photo.id)));
            },

            receiptPhotoList(row = this.activeRow) {
                const list = Array.isArray(row?.photos?.receipt) ? row.photos.receipt : [];
                return list.filter((photo) => !this.removePhotoIds.includes(Number(photo.id)));
            },

            fallbackPhotoCount(row = this.activeRow) {
                const vendor = Array.isArray(row?.photos?.vendor) ? row.photos.vendor.length : 0;
                const pickup = Array.isArray(row?.photos?.pickup) ? row.photos.pickup.length : 0;
                return vendor + pickup;
            },

            hasRequiredPackagePhotos() {
                if (this.fallbackPhotoCount() > 0) return true;
                return this.receiptPhotoList().length + this.photoUploadFiles.length > 0;
            },

            canRemoveActivePhoto() {
                const photo = this.activePackagePhoto();
                return Boolean(photo && photo.source === 'Receipt' && photo.id);
            },

            removeActivePhoto() {
                const photo = this.activePackagePhoto();
                if (!photo || photo.source !== 'Receipt' || !photo.id) return;

                const id = Number(photo.id);
                if (!this.removePhotoIds.includes(id)) {
                    this.removePhotoIds.push(id);
                }

                this.photoPreviewUrls = this.photoPreviewUrls.filter((item) => Number(item.id) !== id);
                if (this.photoPreviewUrls.length === 0) {
                    this.closePackagePhotos();
                    return;
                }
                this.activePhotoIndex = Math.min(this.activePhotoIndex, this.photoPreviewUrls.length - 1);
            },

            openPackagePhotos(row = this.activeRow) {
                const photos = this.rowPhotoList(row);
                if (!photos.length) {
                    window.showToast?.('No photos available for this package.', 'info');
                    return;
                }
                this.photoPreviewPackage = row;
                this.photoPreviewUrls = photos.map((photo) => ({
                    id: photo.id,
                    name: photo.name || 'Package photo',
                    url: photo.url,
                    source: photo.source || row?.photos?.primary_label || 'Photo',
                }));
                this.activePhotoIndex = 0;
                this.photoPreviewOpen = true;
            },

            closePackagePhotos() {
                this.photoPreviewUrls = [];
                this.photoPreviewPackage = null;
                this.activePhotoIndex = 0;
                this.photoPreviewOpen = false;
            },

            activePackagePhoto() {
                return this.photoPreviewUrls[this.activePhotoIndex] || this.photoPreviewUrls[0] || null;
            },

            nextPackagePhoto() {
                if (this.photoPreviewUrls.length <= 1) return;
                this.activePhotoIndex = (this.activePhotoIndex + 1) % this.photoPreviewUrls.length;
            },

            previousPackagePhoto() {
                if (this.photoPreviewUrls.length <= 1) return;
                this.activePhotoIndex = (this.activePhotoIndex - 1 + this.photoPreviewUrls.length) % this.photoPreviewUrls.length;
            },

            defaultLabelCount(row) {
                const count = Math.floor(Number(row?.received_quantity || row?.quantity || 1));
                return Number.isFinite(count) ? Math.max(1, Math.min(500, count)) : 1;
            },

            setPrintLabelCount(value) {
                const count = Math.floor(Number(value || 1));
                this.printForm.label_count = Number.isFinite(count) ? Math.max(1, Math.min(500, count)) : 1;
            },

            openPrintModal(row) {
                this.activeRow = row;
                this.printForm = {
                    label_count: this.defaultLabelCount(row),
                };
                this.printModalOpen = true;
            },

            closePrintModal() {
                if (this.printLoading) return;
                this.printModalOpen = false;
                this.printForm = { label_count: 1 };
            },

            async printLabel() {
                if (!this.activeRow || this.printLoading) return;
                this.setPrintLabelCount(this.printForm.label_count);
                this.printLoading = true;
                try {
                    const response = await fetch(withItem(config.print_label_url, this.activeRow.id), {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify({ label_count: this.printForm.label_count }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Unable to print label.');
                    if (result.data?.label_html) {
                        const win = window.open('', '_blank');
                        if (!win) {
                            window.showToast?.('Pop-up blocked. Please allow pop-ups.', 'warning');
                            return;
                        }
                        win.document.write(result.data.label_html);
                        win.document.close();
                        window.setTimeout(() => { win.focus(); win.print(); }, 250);
                    }
                    this.printModalOpen = false;
                    window.showToast?.(result.message || 'Label ready.', 'success');
                    this.loadData();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to print label.', 'error');
                } finally {
                    this.printLoading = false;
                }
            },

            replaceRow(row) {
                const index = this.rows.findIndex((item) => item.id === row.id);
                if (index >= 0) this.rows.splice(index, 1, row);
            },

            stageClass(tone) {
                switch (tone) {
                    case 'emerald': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
                    case 'blue': return 'bg-blue-50 text-blue-700 ring-1 ring-blue-200';
                    case 'violet': return 'bg-violet-50 text-violet-700 ring-1 ring-violet-200';
                    case 'amber': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
                    case 'rose': return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
                    default: return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
                }
            },

            paymentClass(label) {
                const normalized = (label || '').toLowerCase();
                if (normalized.includes('paid')) return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
                if (normalized.includes('waived') || normalized.includes('override')) return 'bg-blue-50 text-blue-700 ring-1 ring-blue-200';
                if (normalized.includes('due')) return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
                return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
            },

            tableHeaderClass(key) {
                if (key === 'actions') return 'text-right';
                if (['qty', 'stage'].includes(key)) return 'text-center';
                return 'text-left';
            },

            tableHeaderContentClass(key) {
                if (key === 'actions') return 'justify-end';
                if (['qty', 'stage'].includes(key)) return 'justify-center';
                return '';
            },

            paymentAmount(payment) {
                const amount = Number(payment?.amount);
                if (Number.isFinite(amount)) return `₵${this.formatMoney(amount)}`;
                return payment?.amount_label || '';
            },

            formatMoney(amount) {
                const parsed = Number(amount || 0);
                return parsed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            initDateRange() {
                const setupPicker = (refName, fromKey, toKey) => {
                    if (!this.$refs[refName]) return;
                    if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;
                    const $input = window.$(this.$refs[refName]);
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
                        this.filters[fromKey] = picker.startDate.format('YYYY-MM-DD');
                        this.filters[toKey] = picker.endDate.format('YYYY-MM-DD');
                        $input.val(`${this.filters[fromKey]} - ${this.filters[toKey]}`);
                    });
                    $input.on('cancel.daterangepicker', () => {
                        this.filters[fromKey] = '';
                        this.filters[toKey] = '';
                        $input.val('');
                    });
                };
                const setupPickers = () => {
                    setupPicker('dateRange', 'date_from', 'date_to');
                    setupPicker('deliveredDateRange', 'delivered_date_from', 'delivered_date_to');
                };
                if (window.$ && window.moment && window.$.fn.daterangepicker) {
                    setupPickers();
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
                    .then(setupPickers);
            },
        };
    });
}

function registerWarehousePackageShowPage() {
    if (!window.Alpine) return;

    const container = document.querySelector('[data-warehouse-package-show-config]');
    if (!container) return;
    const detailConfig = parseJsonAttribute(container, 'data-warehouse-package-show-config', null);
    if (!detailConfig?.package) return;

    window.Alpine.data('warehousePackageShowPage', () => ({
        config: detailConfig,
        pkg: detailConfig.package,
        permissions: detailConfig.permissions || {},
        activeRow: detailConfig.package,
        editModalOpen: false,
        printModalOpen: false,
        paymentModalOpen: false,
        photoPreviewOpen: false,
        modalLoading: false,
        printLoading: false,
        paymentLoading: false,
        paymentWalletChanging: false,
        editForm: {},
        photoUploadFiles: [],
        removePhotoIds: [],
        printForm: { label_count: 1 },
        paymentForm: { amount: '', payment_wallet_id: '', payment_reference: '', outcome: 'answered', payment_receipt_name: '', notes: '' },
        photoPreviewUrls: [],
        activePhotoIndex: 0,

        badgeClass(tone) {
            switch (tone) {
                case 'emerald': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
                case 'blue': return 'bg-blue-50 text-blue-700 ring-1 ring-blue-200';
                case 'violet': return 'bg-violet-50 text-violet-700 ring-1 ring-violet-200';
                case 'amber': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
                case 'rose': return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
                default: return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
            }
        },

        paymentClass(label) {
            const normalized = (label || '').toLowerCase();
            if (normalized.includes('paid')) return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (normalized.includes('waived') || normalized.includes('override')) return 'bg-blue-50 text-blue-700 ring-1 ring-blue-200';
            if (normalized.includes('due')) return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
        },

        paymentAmount(payment = this.pkg.payment) {
            const amount = Number(payment?.amount);
            if (Number.isFinite(amount)) return `₵${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            return payment?.amount_label || 'No fee set';
        },

        activeWallets() {
            return Array.isArray(this.config.wallets) ? this.config.wallets : [];
        },

        selectedPaymentWallet() {
            const selectedId = String(this.paymentForm.payment_wallet_id || '');
            return this.activeWallets().find((wallet) => String(wallet.id) === selectedId) || null;
        },

        selectedWalletLabel(wallet = this.selectedPaymentWallet()) {
            if (!wallet) return '';
            return [wallet.name, wallet.provider, wallet.phone_number].filter(Boolean).join(' / ');
        },

        selectedWalletHasOpenSession() {
            const wallet = this.selectedPaymentWallet();
            return Boolean(wallet?.has_open_session);
        },

        canUsePaymentTask() {
            const task = this.pkg.payment_task || {};
            const assignedTo = Number(task.assigned_to_user_id || 0);
            if (!assignedTo) return true;
            if (assignedTo === Number(this.config.current_user_id || 0)) return true;
            return Boolean(this.permissions.can_assign_payments || this.permissions.can_override_payments || this.permissions.can_manage_wallets);
        },

        paymentTaskBlockedMessage() {
            if (this.canUsePaymentTask()) return '';
            return `This payment is assigned to ${this.pkg.payment_task?.assigned_to || 'another user'}. Ask a supervisor to reassign it before processing.`;
        },

        canSavePaymentDetails() {
            const amount = Number(this.paymentForm.amount || 0);
            return Boolean(!this.paymentLoading && this.permissions.can_process_payments && this.canUsePaymentTask() && Number.isFinite(amount) && amount >= 0);
        },

        canSubmitPayment() {
            const amount = Number(this.paymentForm.amount || 0);
            return Boolean(this.canSavePaymentDetails() && Number.isFinite(amount) && amount > 0 && this.paymentForm.outcome && this.selectedPaymentWallet() && this.selectedWalletHasOpenSession());
        },

        paymentSessionMessage() {
            const wallet = this.selectedPaymentWallet();
            if (!wallet) return 'Select an assigned wallet before recording payment.';
            if (!wallet.has_open_session) return 'Start a payment session for this wallet before saving the actual payment.';
            return `Open session today${wallet.session_started_at ? ` since ${wallet.session_started_at}` : ''}.`;
        },

        openEditModal() {
            const row = this.pkg;
            this.activeRow = row;
            this.editForm = {
                description: row.item_description || '',
                quantity: row.quantity || row.received_quantity || 1,
                delivery_recipient_name: row.recipient_name || '',
                delivery_recipient_phone: row.recipient_phone || '',
                delivery_region_id: row.delivery_location?.region_id || '',
                delivery_district_id: row.delivery_location?.district_id || '',
                delivery_town: row.delivery_location?.town || row.destination?.split(',')[0] || '',
                locationQuery: row.delivery_location?.display || row.destination || '',
                locationResults: [],
                selectedLocation: null,
                _showDropdown: false,
                delivery_landmark: row.delivery_landmark || '',
                delivery_instructions: row.delivery_instructions || '',
                delivery_method: row.delivery_method || 'direct',
                forward_to_warehouse_id: row.forward_to_warehouse_id || '',
            };
            this.photoUploadFiles = [];
            this.removePhotoIds = [];
            this.editModalOpen = true;
        },

        closeEditModal() {
            if (this.modalLoading) return;
            this.editModalOpen = false;
            this.editForm = {};
            this.photoUploadFiles = [];
            this.removePhotoIds = [];
        },

        canSaveEditPackage() {
            return Boolean(!this.modalLoading && (this.editForm.description || '').trim() && Number(this.editForm.quantity || 0) > 0 && this.hasRequiredPackagePhotos());
        },

        async savePackage() {
            if (!this.canSaveEditPackage()) return;
            this.modalLoading = true;
            try {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                ['description', 'quantity', 'delivery_recipient_name', 'delivery_recipient_phone', 'delivery_region_id', 'delivery_district_id', 'delivery_landmark', 'delivery_instructions', 'delivery_method', 'forward_to_warehouse_id'].forEach((key) => {
                    formData.append(key, this.editForm[key] || '');
                });
                formData.append('delivery_town', this.editForm.delivery_town || this.editForm.locationQuery || '');
                this.photoUploadFiles.forEach((file) => formData.append('photos[]', file));
                this.removePhotoIds.forEach((id) => formData.append('remove_photo_ids[]', id));

                const response = await fetch(this.config.update_url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
                    body: formData,
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to update package.');
                window.showToast?.(result.message || 'Package updated.', 'success');
                window.location.reload();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to update package.', 'error');
            } finally {
                this.modalLoading = false;
            }
        },

        searchLocation(target) {
            const query = (target.locationQuery || '').trim();
            target.selectedLocation = null;
            target.delivery_region_id = '';
            target.delivery_district_id = '';
            target.delivery_town = query;
            if (query.length < 2) {
                target.locationResults = [];
                target._showDropdown = false;
                return;
            }
            clearTimeout(target._timeout);
            target._timeout = setTimeout(async () => {
                try {
                    const response = await fetch(`${this.config.location_search_url}?q=${encodeURIComponent(query)}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const result = await response.json();
                    target.locationResults = result.locations || [];
                    target._showDropdown = target.locationResults.length > 0;
                } catch {
                    target.locationResults = [];
                }
            }, 250);
        },

        selectLocation(target, location) {
            clearTimeout(target._timeout);
            target.locationQuery = location.display;
            target.delivery_region_id = location.region?.id || '';
            target.delivery_district_id = location.district?.id || '';
            target.delivery_town = location.name || location.display;
            target.locationResults = [];
            target._showDropdown = false;
        },

        closeLocationDropdownSoon(target) {
            setTimeout(() => { target._showDropdown = false; }, 120);
        },

        handlePackagePhotos(event) {
            this.photoUploadFiles = Array.from(event.target.files || []);
        },

        rowPhotoList(row = this.pkg) {
            const list = Array.isArray(row?.photos?.primary) ? row.photos.primary : [];
            return list.filter((photo) => !this.removePhotoIds.includes(Number(photo.id)));
        },

        receiptPhotoList(row = this.pkg) {
            const list = Array.isArray(row?.photos?.receipt) ? row.photos.receipt : [];
            return list.filter((photo) => !this.removePhotoIds.includes(Number(photo.id)));
        },

        fallbackPhotoCount(row = this.pkg) {
            return (Array.isArray(row?.photos?.vendor) ? row.photos.vendor.length : 0) + (Array.isArray(row?.photos?.pickup) ? row.photos.pickup.length : 0);
        },

        hasRequiredPackagePhotos() {
            if (this.fallbackPhotoCount() > 0) return true;
            return this.receiptPhotoList().length + this.photoUploadFiles.length > 0;
        },

        openPackagePhotos(row = this.pkg) {
            const photos = this.rowPhotoList(row);
            if (!photos.length) {
                window.showToast?.('No photos available for this package.', 'info');
                return;
            }
            this.photoPreviewUrls = photos.map((photo) => ({
                id: photo.id,
                name: photo.name || 'Package photo',
                url: photo.url,
                source: photo.source || row?.photos?.primary_label || 'Photo',
            }));
            this.activePhotoIndex = 0;
            this.photoPreviewOpen = true;
        },

        closePackagePhotos() {
            this.photoPreviewUrls = [];
            this.activePhotoIndex = 0;
            this.photoPreviewOpen = false;
        },

        activePackagePhoto() {
            return this.photoPreviewUrls[this.activePhotoIndex] || this.photoPreviewUrls[0] || null;
        },

        nextPackagePhoto() {
            if (this.photoPreviewUrls.length <= 1) return;
            this.activePhotoIndex = (this.activePhotoIndex + 1) % this.photoPreviewUrls.length;
        },

        previousPackagePhoto() {
            if (this.photoPreviewUrls.length <= 1) return;
            this.activePhotoIndex = (this.activePhotoIndex - 1 + this.photoPreviewUrls.length) % this.photoPreviewUrls.length;
        },

        canRemoveActivePhoto() {
            const photo = this.activePackagePhoto();
            return Boolean(photo && photo.source === 'Receipt' && photo.id);
        },

        removeActivePhoto() {
            const photo = this.activePackagePhoto();
            if (!photo || photo.source !== 'Receipt' || !photo.id) return;
            const id = Number(photo.id);
            if (!this.removePhotoIds.includes(id)) this.removePhotoIds.push(id);
            this.photoPreviewUrls = this.photoPreviewUrls.filter((item) => Number(item.id) !== id);
            if (!this.photoPreviewUrls.length) return this.closePackagePhotos();
            this.activePhotoIndex = Math.min(this.activePhotoIndex, this.photoPreviewUrls.length - 1);
        },

        defaultLabelCount(row = this.pkg) {
            const count = Math.floor(Number(row?.received_quantity || row?.quantity || 1));
            return Number.isFinite(count) ? Math.max(1, Math.min(500, count)) : 1;
        },

        setPrintLabelCount(value) {
            const count = Math.floor(Number(value || 1));
            this.printForm.label_count = Number.isFinite(count) ? Math.max(1, Math.min(500, count)) : 1;
        },

        openPrintModal() {
            this.printForm = { label_count: this.defaultLabelCount() };
            this.printModalOpen = true;
        },

        async printLabel() {
            if (this.printLoading) return;
            this.setPrintLabelCount(this.printForm.label_count);
            this.printLoading = true;
            try {
                const response = await fetch(this.config.print_label_url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
                    body: JSON.stringify({ label_count: this.printForm.label_count }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to print labels.');
                const win = window.open('', '_blank');
                if (!win) {
                    window.showToast?.('Pop-up blocked. Please allow pop-ups.', 'warning');
                    return;
                }
                win.document.write(result.data?.label_html || '');
                win.document.close();
                window.setTimeout(() => { win.focus(); win.print(); }, 250);
                this.printModalOpen = false;
                window.showToast?.(result.message || 'Labels ready.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to print labels.', 'error');
            } finally {
                this.printLoading = false;
            }
        },

        openPaymentModal() {
            const activeWallets = this.activeWallets();
            const existingWalletId = this.pkg.payment?.payment_wallet_id;
            const fallbackWalletId = activeWallets.length ? activeWallets[0].id : '';
            this.paymentForm = {
                amount: this.pkg.payment?.amount || '',
                payment_wallet_id: existingWalletId || fallbackWalletId,
                payment_reference: this.pkg.payment?.reference || '',
                outcome: 'answered',
                payment_receipt_name: '',
                notes: '',
            };
            this.paymentWalletChanging = false;
            if (this.$refs.packagePaymentReceiptInput) this.$refs.packagePaymentReceiptInput.value = '';
            this.paymentModalOpen = true;
        },

        handlePackagePaymentReceiptChange(event) {
            const file = event.target.files?.[0] || null;
            this.paymentForm.payment_receipt_name = file ? file.name : '';
        },

        async saveDeliveryFee() {
            if (this.paymentLoading) return;
            if (!this.canUsePaymentTask()) {
                window.showToast?.(this.paymentTaskBlockedMessage(), 'error');
                return;
            }
            const amount = Number(this.paymentForm.amount || 0);
            if (!Number.isFinite(amount) || amount < 0) {
                window.showToast?.('Enter a valid delivery fee amount.', 'error');
                return;
            }
            this.paymentLoading = true;
            try {
                const response = await fetch(this.config.delivery_fee_url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
                    body: JSON.stringify({ amount: this.paymentForm.amount, notes: this.paymentForm.notes }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to save delivery fee.');
                this.pkg = result.data;
                this.activeRow = this.pkg;
                this.paymentModalOpen = false;
                window.showToast?.(result.message || 'Delivery fee saved.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to save delivery fee.', 'error');
            } finally {
                this.paymentLoading = false;
            }
        },

        async markPaid() {
            if (this.paymentLoading) return;
            if (!this.canUsePaymentTask()) {
                window.showToast?.(this.paymentTaskBlockedMessage(), 'error');
                return;
            }
            const amount = Number(this.paymentForm.amount || 0);
            if (!Number.isFinite(amount) || amount <= 0) {
                window.showToast?.('Enter the delivery fee before marking payment as paid.', 'error');
                return;
            }
            if (!this.paymentForm.payment_wallet_id) {
                window.showToast?.('Select the payment wallet before marking payment as paid.', 'error');
                return;
            }
            if (!this.selectedWalletHasOpenSession()) {
                window.showToast?.('Start a payment session for this wallet before recording payment.', 'error');
                return;
            }
            if (!this.paymentForm.outcome) {
                window.showToast?.('Select the call result before marking payment as paid.', 'error');
                return;
            }
            this.paymentLoading = true;
            try {
                const payload = new FormData();
                payload.append('amount', this.paymentForm.amount);
                payload.append('payment_wallet_id', this.paymentForm.payment_wallet_id);
                payload.append('payment_reference', this.paymentForm.payment_reference || '');
                payload.append('outcome', this.paymentForm.outcome || 'answered');
                payload.append('notes', this.paymentForm.notes || '');
                const receipt = this.$refs.packagePaymentReceiptInput?.files?.[0] || null;
                if (receipt) payload.append('payment_receipt', receipt);
                const response = await fetch(this.config.mark_paid_url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken() },
                    body: payload,
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to mark payment paid.');
                this.pkg = result.data;
                this.activeRow = this.pkg;
                this.paymentModalOpen = false;
                window.showToast?.(result.message || 'Payment marked paid.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to mark payment paid.', 'error');
            } finally {
                this.paymentLoading = false;
            }
        },
    }));
}

if (window.Alpine) {
    registerWarehousePackagesPage();
    registerWarehousePackageShowPage();
} else {
    document.addEventListener('alpine:init', () => {
        registerWarehousePackagesPage();
        registerWarehousePackageShowPage();
    });
}
