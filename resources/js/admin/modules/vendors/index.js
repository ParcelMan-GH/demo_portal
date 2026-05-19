import './view.js';
import Alpine from 'alpinejs';

/**
 * Vendors page Alpine component
 */

function buildVendorsTable(config) {
    return {
        endpoint: config.endpoint,
        exportEndpoint: config.exportEndpoint,
        storeEndpoint: config.storeEndpoint,
        baseEndpoint: config.baseEndpoint,
        csrfToken: config.csrfToken,
        vendors: [],
        meta: {
            current_page: 1,
            from: 0,
            to: 0,
            total: 0,
            last_page: 1,
            summary: {
                total_vendors: 0,
                active_vendors: 0,
                vendors_with_shipments: 0,
                open_shipments: 0,
                delivered_shipments: 0,
                unpaid_earnings: 0,
                available_balance: 0,
                pending_payouts: 0,
                total_paid: 0,
                eligible_payout_vendors: 0,
            },
        },
        loading: false,
        showFilters: false,
        search: '',
        statusFilter: '',
        statusFilterName: 'All statuses',
        createdFrom: '',
        createdTo: '',
        hasEmail: '',
        hasBusinessName: '',
        hasPushToken: '',
        hasCommissionOverride: '',
        commissionMin: '',
        commissionMax: '',
        shipmentCountMin: '',
        shipmentCountMax: '',
        shipmentStatus: '',
        shipmentSource: '',
        destinationMode: '',
        lastShipmentFrom: '',
        lastShipmentTo: '',
        activityState: '',
        lastActivityFrom: '',
        lastActivityTo: '',
        earningsStatus: '',
        unpaidEarningsMin: '',
        unpaidEarningsMax: '',
        dateRangePicker: null,
        lastShipmentRangePicker: null,
        lastActivityRangePicker: null,
        perPage: 50,
        sortBy: 'created_at',
        sortDirection: 'desc',
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'business_name', label: 'Business Name' },
            { key: 'email', label: 'Email' },
            { key: 'phone', label: 'Phone' },
            { key: 'status', label: 'Status' },
            { key: 'shipments', label: 'Shipments' },
            { key: 'delivered_shipments', label: 'Delivered' },
            { key: 'open_shipments', label: 'Open' },
            { key: 'cancelled_shipments', label: 'Cancelled' },
            { key: 'last_shipment_at', label: 'Last Shipment' },
            { key: 'last_activity_at', label: 'Last Activity' },
            { key: 'total_earnings', label: 'Total Earnings' },
            { key: 'unpaid_earnings', label: 'Unpaid Earnings' },
            { key: 'total_paid', label: 'Total Paid' },
            { key: 'push_ready', label: 'Push Ready' },
            { key: 'created_at', label: 'Created At' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            name: true,
            business_name: true,
            email: true,
            phone: true,
            status: true,
            shipments: true,
            delivered_shipments: true,
            open_shipments: true,
            cancelled_shipments: false,
            last_shipment_at: true,
            last_activity_at: false,
            total_earnings: false,
            unpaid_earnings: false,
            total_paid: false,
            push_ready: false,
            created_at: true,
            actions: true,
        },

        // Modal state
        showModal: false,
        modalMode: 'add', // 'add', 'edit', 'view'
        editingVendorId: null,
        saving: false,
        errors: {},
        form: {
            name: '',
            business_name: '',
            email: '',
            phone: '',
            is_active: true,
            commission_rate_override: '',
        },
        init() {
            this.initDateRange();
            this.loadData();
        },

        appendIfFilled(params, key, value) {
            if (value !== '' && value !== null && value !== undefined) {
                params.append(key, value);
            }
        },

        appendCurrentFilters(params) {
            this.appendIfFilled(params, 'search', this.search);
            this.appendIfFilled(params, 'status', this.statusFilter);
            this.appendIfFilled(params, 'date_from', this.createdFrom);
            this.appendIfFilled(params, 'date_to', this.createdTo);
            this.appendIfFilled(params, 'has_email', this.hasEmail);
            this.appendIfFilled(params, 'has_business_name', this.hasBusinessName);
            this.appendIfFilled(params, 'has_push_token', this.hasPushToken);
            this.appendIfFilled(params, 'has_commission_override', this.hasCommissionOverride);
            this.appendIfFilled(params, 'commission_min', this.commissionMin);
            this.appendIfFilled(params, 'commission_max', this.commissionMax);
            this.appendIfFilled(params, 'shipment_count_min', this.shipmentCountMin);
            this.appendIfFilled(params, 'shipment_count_max', this.shipmentCountMax);
            this.appendIfFilled(params, 'shipment_status', this.shipmentStatus);
            this.appendIfFilled(params, 'shipment_source', this.shipmentSource);
            this.appendIfFilled(params, 'destination_mode', this.destinationMode);
            this.appendIfFilled(params, 'last_shipment_from', this.lastShipmentFrom);
            this.appendIfFilled(params, 'last_shipment_to', this.lastShipmentTo);
            this.appendIfFilled(params, 'activity_state', this.activityState);
            this.appendIfFilled(params, 'last_activity_from', this.lastActivityFrom);
            this.appendIfFilled(params, 'last_activity_to', this.lastActivityTo);
            this.appendIfFilled(params, 'earnings_status', this.earningsStatus);
            this.appendIfFilled(params, 'unpaid_earnings_min', this.unpaidEarningsMin);
            this.appendIfFilled(params, 'unpaid_earnings_max', this.unpaidEarningsMax);
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

                this.appendCurrentFilters(params);

                const response = await fetch(`${this.endpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to fetch data');

                const result = await response.json();
                this.vendors = result.data;
                this.meta = {
                    current_page: result.meta.current_page,
                    from: result.meta.from,
                    to: result.meta.to,
                    total: result.meta.total,
                    last_page: result.meta.last_page,
                    summary: result.meta.summary || this.meta.summary,
                };
            } catch (error) {
                console.error('Error loading vendors:', error);
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

        setStatusFilter(value, label) {
            this.statusFilter = value;
            this.statusFilterName = label;
            this.meta.current_page = 1;
            this.loadData();
        },

        clearStatusFilter() {
            this.statusFilter = '';
            this.statusFilterName = 'All statuses';
            this.meta.current_page = 1;
            this.loadData();
        },

        applyFilters() {
            this.statusFilterName = this.statusLabelForFilter(this.statusFilter);
            this.meta.current_page = 1;
            this.loadData();
        },

        clearFilters() {
            this.search = '';
            this.statusFilter = '';
            this.statusFilterName = 'All statuses';
            this.createdFrom = '';
            this.createdTo = '';
            this.resetAdvancedFilters();
            if (this.$refs.createdRange) this.$refs.createdRange.value = '';
            if (this.$refs.lastShipmentRange) this.$refs.lastShipmentRange.value = '';
            if (this.$refs.lastActivityRange) this.$refs.lastActivityRange.value = '';
            this.meta.current_page = 1;
            this.loadData();
        },

        resetAdvancedFilters() {
            this.hasEmail = '';
            this.hasBusinessName = '';
            this.hasPushToken = '';
            this.hasCommissionOverride = '';
            this.commissionMin = '';
            this.commissionMax = '';
            this.shipmentCountMin = '';
            this.shipmentCountMax = '';
            this.shipmentStatus = '';
            this.shipmentSource = '';
            this.destinationMode = '';
            this.lastShipmentFrom = '';
            this.lastShipmentTo = '';
            this.activityState = '';
            this.lastActivityFrom = '';
            this.lastActivityTo = '';
            this.earningsStatus = '';
            this.unpaidEarningsMin = '';
            this.unpaidEarningsMax = '';
        },

        statusLabelForFilter(value) {
            switch (value) {
                case 'active':
                    return 'Active';
                case 'inactive':
                    return 'Inactive';
                case 'deleted':
                    return 'Deleted';
                default:
                    return 'All statuses';
            }
        },

        clearFilter(key) {
            if (key === 'all') {
                this.search = '';
                this.statusFilter = '';
                this.statusFilterName = 'All statuses';
                this.createdFrom = '';
                this.createdTo = '';
                this.resetAdvancedFilters();
                if (this.$refs.createdRange) this.$refs.createdRange.value = '';
                if (this.$refs.lastShipmentRange) this.$refs.lastShipmentRange.value = '';
                if (this.$refs.lastActivityRange) this.$refs.lastActivityRange.value = '';
            }

            if (key === 'status') {
                this.statusFilter = '';
                this.statusFilterName = 'All statuses';
            }

            if (key === 'date') {
                this.createdFrom = '';
                this.createdTo = '';
                if (this.$refs.createdRange) this.$refs.createdRange.value = '';
            }

            const resets = {
                has_email: 'hasEmail',
                has_business_name: 'hasBusinessName',
                has_push_token: 'hasPushToken',
                has_commission_override: 'hasCommissionOverride',
                commission: ['commissionMin', 'commissionMax'],
                shipment_count: ['shipmentCountMin', 'shipmentCountMax'],
                shipment_status: 'shipmentStatus',
                shipment_source: 'shipmentSource',
                destination_mode: 'destinationMode',
                last_shipment: ['lastShipmentFrom', 'lastShipmentTo'],
                activity_state: 'activityState',
                last_activity: ['lastActivityFrom', 'lastActivityTo'],
                earnings_status: 'earningsStatus',
                unpaid_earnings: ['unpaidEarningsMin', 'unpaidEarningsMax'],
            };

            const target = resets[key];
            if (Array.isArray(target)) {
                target.forEach((field) => {
                    this[field] = '';
                });
            } else if (target) {
                this[target] = '';
            }

            if (key === 'last_shipment' && this.$refs.lastShipmentRange) {
                this.$refs.lastShipmentRange.value = '';
            }

            if (key === 'last_activity' && this.$refs.lastActivityRange) {
                this.$refs.lastActivityRange.value = '';
            }

            this.meta.current_page = 1;
            this.loadData();
        },

        activeFilterChips() {
            const chips = [];
            if (this.statusFilter) chips.push({ key: 'status', label: `Status: ${this.statusFilterName}` });
            if (this.createdFrom || this.createdTo) chips.push({ key: 'date', label: `Created: ${this.createdFrom || '...'} - ${this.createdTo || '...'}` });
            if (this.hasEmail) chips.push({ key: 'has_email', label: `Email: ${this.yesNoLabel(this.hasEmail)}` });
            if (this.hasBusinessName) chips.push({ key: 'has_business_name', label: `Business: ${this.yesNoLabel(this.hasBusinessName)}` });
            if (this.hasPushToken) chips.push({ key: 'has_push_token', label: `Push: ${this.yesNoLabel(this.hasPushToken)}` });
            if (this.hasCommissionOverride) chips.push({ key: 'has_commission_override', label: `Commission Override: ${this.yesNoLabel(this.hasCommissionOverride)}` });
            if (this.commissionMin || this.commissionMax) chips.push({ key: 'commission', label: `Commission: ${this.commissionMin || '0'} - ${this.commissionMax || 'Any'}` });
            if (this.shipmentCountMin || this.shipmentCountMax) chips.push({ key: 'shipment_count', label: `Shipments: ${this.shipmentCountMin || '0'} - ${this.shipmentCountMax || 'Any'}` });
            if (this.shipmentStatus) chips.push({ key: 'shipment_status', label: `Shipment Status: ${this.shipmentStatusLabel(this.shipmentStatus)}` });
            if (this.shipmentSource) chips.push({ key: 'shipment_source', label: `Source: ${this.shipmentSourceLabel(this.shipmentSource)}` });
            if (this.destinationMode) chips.push({ key: 'destination_mode', label: `Destination: ${this.destinationModeLabel(this.destinationMode)}` });
            if (this.lastShipmentFrom || this.lastShipmentTo) chips.push({ key: 'last_shipment', label: `Last Shipment: ${this.lastShipmentFrom || '...'} - ${this.lastShipmentTo || '...'}` });
            if (this.activityState) chips.push({ key: 'activity_state', label: `Activity: ${this.activityStateLabel(this.activityState)}` });
            if (this.lastActivityFrom || this.lastActivityTo) chips.push({ key: 'last_activity', label: `Last Activity: ${this.lastActivityFrom || '...'} - ${this.lastActivityTo || '...'}` });
            if (this.earningsStatus) chips.push({ key: 'earnings_status', label: `Earnings: ${this.financialStatusLabel(this.earningsStatus)}` });
            if (this.unpaidEarningsMin || this.unpaidEarningsMax) chips.push({ key: 'unpaid_earnings', label: `Unpaid: ${this.unpaidEarningsMin || '0'} - ${this.unpaidEarningsMax || 'Any'}` });
            return chips;
        },

        yesNoLabel(value) {
            return value === 'yes' ? 'Yes' : 'No';
        },

        shipmentStatusLabel(value) {
            return value.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        },

        shipmentSourceLabel(value) {
            return {
                vendor_app: 'Vendor App',
                admin_walkin: 'Walk-in',
                warehouse_walkin: 'Warehouse Walk-in',
            }[value] || value;
        },

        destinationModeLabel(value) {
            return value === 'per_item' ? 'Per Package' : 'Single Destination';
        },

        activityStateLabel(value) {
            return {
                has_activity: 'Has activity',
                never_logged_in: 'Never logged in',
            }[value] || value;
        },

        financialStatusLabel(value) {
            return value === 'none' ? 'None' : this.shipmentStatusLabel(value);
        },

        activeCount() {
            return this.vendors.filter((vendor) => !vendor.is_deleted && vendor.is_active).length;
        },

        inactiveCount() {
            return this.vendors.filter((vendor) => !vendor.is_deleted && !vendor.is_active).length;
        },

        deletedCount() {
            return this.vendors.filter((vendor) => vendor.is_deleted).length;
        },

        vendorStatusLabel(vendor) {
            if (vendor.is_deleted) return 'Deleted';
            return vendor.is_active ? 'Active' : 'Inactive';
        },

        statusBadgeClass(vendor) {
            if (vendor.is_deleted) return 'border-rose-200 bg-rose-50 text-rose-700';
            if (vendor.is_active) return 'border-orange-200 bg-orange-50 text-orange-700';
            return 'border-amber-200 bg-amber-50 text-amber-700';
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        visibleColumnCount() {
            return Object.values(this.visibleColumns).filter(Boolean).length;
        },

        initDateRange() {
            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker) return;
                this.dateRangePicker = this.setupDateRangePicker(this.$refs.createdRange, 'createdFrom', 'createdTo', () => this.clearDateFilter());
                this.lastShipmentRangePicker = this.setupDateRangePicker(this.$refs.lastShipmentRange, 'lastShipmentFrom', 'lastShipmentTo', () => this.clearLastShipmentFilter());
                this.lastActivityRangePicker = this.setupDateRangePicker(this.$refs.lastActivityRange, 'lastActivityFrom', 'lastActivityTo', () => this.clearLastActivityFilter());
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            // Load from CDN
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

        setupDateRangePicker(input, fromField, toField, clearCallback) {
            if (!input || !window.$ || !window.moment || !window.$.fn.daterangepicker) return null;

            const $input = window.$(input);

            $input.daterangepicker({
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                opens: 'right',
                locale: {
                    format: 'YYYY-MM-DD',
                    cancelLabel: 'Clear',
                },
                ranges: {
                    'Today': [window.moment(), window.moment()],
                    'Yesterday': [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                    'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                    'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                    'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                    'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                },
            });

            $input.on('apply.daterangepicker', (ev, picker) => {
                this[fromField] = picker.startDate.format('YYYY-MM-DD');
                this[toField] = picker.endDate.format('YYYY-MM-DD');
                $input.val(`${this[fromField]} - ${this[toField]}`);
                this.meta.current_page = 1;
                this.loadData();
            });

            $input.on('cancel.daterangepicker', () => {
                clearCallback();
            });

            return $input.data('daterangepicker');
        },

        clearDateFilter() {
            this.createdFrom = '';
            this.createdTo = '';
            if (this.dateRangePicker) {
                this.dateRangePicker.setStartDate(window.moment());
                this.dateRangePicker.setEndDate(window.moment());
            }
            if (this.$refs.createdRange) {
                this.$refs.createdRange.value = '';
            }
            this.loadData();
        },

        clearLastShipmentFilter() {
            this.lastShipmentFrom = '';
            this.lastShipmentTo = '';
            if (this.lastShipmentRangePicker) {
                this.lastShipmentRangePicker.setStartDate(window.moment());
                this.lastShipmentRangePicker.setEndDate(window.moment());
            }
            if (this.$refs.lastShipmentRange) {
                this.$refs.lastShipmentRange.value = '';
            }
            this.loadData();
        },

        clearLastActivityFilter() {
            this.lastActivityFrom = '';
            this.lastActivityTo = '';
            if (this.lastActivityRangePicker) {
                this.lastActivityRangePicker.setStartDate(window.moment());
                this.lastActivityRangePicker.setEndDate(window.moment());
            }
            if (this.$refs.lastActivityRange) {
                this.$refs.lastActivityRange.value = '';
            }
            this.loadData();
        },

        setPerPage(value) {
            this.perPage = value;
            this.meta.current_page = 1;
            this.loadData();
        },

        nextPage() {
            if (this.meta.current_page < this.meta.last_page) {
                this.meta.current_page++;
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

        previousPage() {
            if (this.meta.current_page > 1) {
                this.meta.current_page--;
                this.loadData();
            }
        },

        // Modal methods
        openAddModal() {
            this.modalMode = 'add';
            this.editingVendorId = null;
            this.errors = {};
            this.form = {
                name: '',
                business_name: '',
                email: '',
                phone: '',
                is_active: true,
                commission_rate_override: '',
            };
            this.showModal = true;
        },

        openEditModal(vendor) {
            this.modalMode = 'edit';
            this.editingVendorId = vendor.id;
            this.errors = {};
            this.form = {
                name: vendor.name,
                business_name: vendor.business_name || '',
                email: vendor.email,
                phone: vendor.phone,
                is_active: vendor.is_active,
                commission_rate_override: vendor.commission_rate_override ?? '',
            };
            this.showModal = true;
        },

        viewVendor(vendor) {
            this.modalMode = 'view';
            this.editingVendorId = vendor.id;
            this.errors = {};
            this.form = {
                name: vendor.name,
                business_name: vendor.business_name || '',
                email: vendor.email,
                phone: vendor.phone,
                is_active: vendor.is_active,
                commission_rate_override: vendor.commission_rate_override ?? '',
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.errors = {};
        },

        async saveVendor() {
            this.saving = true;
            this.errors = {};

            try {
                const url = this.modalMode === 'add'
                    ? this.storeEndpoint
                    : `${this.baseEndpoint}/${this.editingVendorId}`;

                const method = this.modalMode === 'add' ? 'POST' : 'PUT';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.form),
                });

                const result = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        this.errors = result.errors || {};
                    } else {
                        throw new Error(result.message || 'Failed to save vendor');
                    }
                    return;
                }

                this.closeModal();
                this.loadData();
            } catch (error) {
                console.error('Error saving vendor:', error);
            } finally {
                this.saving = false;
            }
        },

        async toggleVendorStatus(vendor) {
            try {
                const response = await fetch(`${this.baseEndpoint}/${vendor.id}/toggle-active`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok) {
                    this.loadData();
                }
            } catch (err) {
                console.error('Failed to update vendor status:', err);
            }
        },

        openDeleteModal(vendor) {
            if (this.$store?.vendorsDelete) {
                this.$store.vendorsDelete.vendor = vendor;
                this.$store.vendorsDelete.onConfirm = () => this.confirmDelete();
                this.$store.vendorsDelete.show = true;
            }
        },

        async confirmDelete() {
            const store = this.$store?.vendorsDelete;
            if (!store?.vendor) return;

            store.deleting = true;

            try {
                const response = await fetch(`${this.baseEndpoint}/${store.vendor.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to delete vendor');
                }

                store.show = false;
                store.vendor = null;
                this.loadData();
            } catch (error) {
                console.error('Error deleting vendor:', error);
                alert('Failed to delete vendor. Please try again.');
            } finally {
                store.deleting = false;
            }
        },

        openRestoreModal(vendor) {
            if (this.$store?.vendorsRestore) {
                this.$store.vendorsRestore.vendor = vendor;
                this.$store.vendorsRestore.onConfirm = () => this.confirmRestore();
                this.$store.vendorsRestore.show = true;
            }
        },

        async confirmRestore() {
            const store = this.$store?.vendorsRestore;
            if (!store?.vendor) return;

            store.restoring = true;

            try {
                const response = await fetch(`${this.baseEndpoint}/${store.vendor.id}/restore`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to restore vendor');
                }

                store.show = false;
                store.vendor = null;

                if (window.showToast) {
                    window.showToast(data.message || 'Vendor restored successfully.', 'success');
                }
                this.loadData();
            } catch (error) {
                console.error('Error restoring vendor:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to restore vendor.', 'error');
                }
            } finally {
                store.restoring = false;
            }
        },

        async exportData(format) {
            try {
                const params = new URLSearchParams();
                this.appendCurrentFilters(params);
                params.append('format', format);

                if (format === 'excel' || format === 'pdf') {
                    window.location.href = `${this.exportEndpoint}?${params}`;
                    return;
                }

                const response = await fetch(`${this.exportEndpoint}?${params}`, {
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
                alert('Export failed. Please try again.');
            }
        },

        async printData() {
            try {
                const params = new URLSearchParams();
                this.appendCurrentFilters(params);

                const response = await fetch(`${this.exportEndpoint}?${params}`, {
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
                alert('Print failed. Please try again.');
            }
        },

        openPrintWindow(data) {
            if (!data.length) {
                alert('No data to print');
                return;
            }

            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Pop-up blocked. Please allow pop-ups to print.');
                return;
            }
            const doc = printWindow.document;
            const headers = Object.keys(data[0]);

            doc.title = 'Vendors Export';
            doc.body.innerHTML = '';

            const style = doc.createElement('style');
            style.textContent = [
                'body { font-family: \"Plus Jakarta Sans\", -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; padding: 20px; }',
                'h1 { font-size: 24px; margin-bottom: 20px; color: #1e293b; }',
                'table { width: 100%; border-collapse: collapse; margin-top: 20px; }',
                'th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; font-size: 12px; }',
                'th { background-color: #f1f5f9; font-weight: 600; color: #475569; }',
                'tr:nth-child(even) { background-color: #f8fafc; }',
            ].join('\n');
            doc.head.appendChild(style);

            const title = doc.createElement('h1');
            title.textContent = 'Vendors List';
            doc.body.appendChild(title);

            const meta = doc.createElement('p');
            meta.style.color = '#64748b';
            meta.style.fontSize = '14px';
            meta.style.marginBottom = '20px';
            meta.textContent = `Generated on ${new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            })}`;
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
            const csvContent = [
                headers.join(','),
                ...data.map(row =>
                    headers.map(header => {
                        let cell = row[header] ?? '';
                        cell = String(cell).replace(/"/g, '""');
                        return `"${cell}"`;
                    }).join(',')
                )
            ].join('\n');

            this.downloadFile(csvContent, 'vendors.csv', 'text/csv');
        },

        downloadFile(content, filename, type) {
            const blob = new Blob([content], { type });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        formatDateTime(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        formatMoney(value) {
            const amount = Number(value || 0);
            return amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
    };
}

function getVendorsConfig() {
    const container = document.querySelector('[data-vendors-config]');
    let config = window.vendorsTableConfig || null;

    if (container) {
        try {
            config = JSON.parse(container.getAttribute('data-vendors-config'));
        } catch (error) {
            console.error('Invalid vendors config JSON:', error);
        }
    }

    if (!config) {
        return null;
    }

    if (!config.csrfToken) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        config.csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    }

    return config;
}

function registerVendorsTable() {
    if (!Alpine) {
        return;
    }

    const config = getVendorsConfig();
    if (!config) {
        return;
    }

    if (!window.vendorsTable) {
        window.vendorsTable = () => buildVendorsTable(config);
    }

    Alpine.store('vendorsDelete', {
        show: false,
        vendor: null,
        deleting: false,
        onConfirm: null,
    });

    Alpine.store('vendorsRestore', {
        show: false,
        vendor: null,
        restoring: false,
        onConfirm: null,
    });

    Alpine.data('vendorsTable', window.vendorsTable);
}

registerVendorsTable();
