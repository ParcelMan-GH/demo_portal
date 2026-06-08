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
            showFilters: false,
            search: '',
            status: '',
            statusName: 'All Statuses',
            dateFrom: '',
            dateTo: '',
            recipientPhone: '',
            location: '',
            packageCount: '',
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

        // Packages state
        packages: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            showFilters: false,
            search: '',
            status: '',
            statusName: 'All Statuses',
            dateFrom: '',
            dateTo: '',
            deliveryMethod: '',
            recipientPhone: '',
            location: '',
            quantityMin: '',
            quantityMax: '',
            page: 1,
            perPage: 10,
            sortBy: 'created_at',
            sortDirection: 'desc',
        },

        // Activity logs state
        activity: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            showFilters: false,
            search: '',
            action: '',
            actionName: 'All Actions',
            dateFrom: '',
            dateTo: '',
            deviceType: '',
            ipAddress: '',
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
            showFilters: false,
            purpose: '',
            purposeName: 'All Purposes',
            status: '',
            dateFrom: '',
            dateTo: '',
            expiresFrom: '',
            expiresTo: '',
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

        payouts: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            showFilters: false,
            search: '',
            status: '',
            method: '',
            page: 1,
            perPage: 10,
            summary: {
                total_earned: 0,
                available_balance: 0,
                total_paid: 0,
                pending_payout: 0,
                last_payout_amount: 0,
                last_payout_at: null,
                min_payout: 0,
                can_request_payout: false,
            },
        },

        showPayoutModal: false,
        payoutSaving: false,
        payoutForm: {
            amount: '',
            payment_method: 'momo',
            payment_phone: '',
            payment_reference: '',
            notes: '',
            confirm_immediately: true,
        },
        showMarkSentModal: false,
        markSentSaving: false,
        markSentPayout: null,
        markSentForm: {
            payment_reference: '',
        },
        showConfirmPayoutModal: false,
        confirmPayoutSaving: false,
        confirmPayoutTarget: null,

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
            is_active: true,
            commission_rate_override: '',
            payout_momo_network: '',
            payout_account_name: '',
            payout_account_number: ''
        },

        init() {
            this.config = window.vendorShowConfig;
            this.vendor = this.config.vendor;
            this.canManage = this.config.canManage;
            this.statuses = this.config.statuses;
            this.payouts.summary = this.config.payoutSummary || this.payouts.summary;
            const params = new URLSearchParams(window.location.search);
            this.payouts.search = params.get('search') || this.payouts.search;
            this.activeTab = this.validTabFromUrl();
            this.loadTab(this.activeTab);
            this.$nextTick(() => this.initDateRanges());
        },

        validTabFromUrl() {
            const validTabs = ['shipments', 'packages', 'activity', 'otp', 'payouts'];
            const tab = new URLSearchParams(window.location.search).get('tab');
            return validTabs.includes(tab) ? tab : 'shipments';
        },

        setActiveTab(tab) {
            const validTabs = ['shipments', 'packages', 'activity', 'otp', 'payouts'];
            if (!validTabs.includes(tab)) return;

            this.activeTab = tab;
            const url = new URL(window.location.href);
            if (tab === 'shipments') {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', tab);
            }
            window.history.pushState({}, '', url.toString());
            this.loadTab(tab);
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

        setShipmentStatus(value, label) {
            this.shipments.status = value;
            this.shipments.statusName = label;
            this.shipments.page = 1;
            this.loadShipments();
        },

        setPackageStatus(value, label) {
            this.packages.status = value;
            this.packages.statusName = label;
            this.packages.page = 1;
            this.loadPackages();
        },

        setActivityAction(value, label) {
            this.activity.action = value;
            this.activity.actionName = label;
            this.activity.page = 1;
            this.loadActivityLogs();
        },

        setOtpPurpose(value, label) {
            this.otp.purpose = value;
            this.otp.purposeName = label;
            this.otp.page = 1;
            this.loadOtpLogs();
        },

        applyTabFilters(tab) {
            this[tab].page = 1;
            this.loadTab(tab);
        },

        clearTabFilters(tab) {
            const resetters = {
                shipments: () => {
                    this.shipments.status = '';
                    this.shipments.statusName = 'All Statuses';
                    this.clearDateRange('shipments');
                    this.shipments.recipientPhone = '';
                    this.shipments.location = '';
                    this.shipments.packageCount = '';
                },
                packages: () => {
                    this.packages.status = '';
                    this.packages.statusName = 'All Statuses';
                    this.clearDateRange('packages');
                    this.packages.deliveryMethod = '';
                    this.packages.recipientPhone = '';
                    this.packages.location = '';
                    this.packages.quantityMin = '';
                    this.packages.quantityMax = '';
                },
                activity: () => {
                    this.activity.action = '';
                    this.activity.actionName = 'All Actions';
                    this.clearDateRange('activity');
                    this.activity.deviceType = '';
                    this.activity.ipAddress = '';
                },
                otp: () => {
                    this.otp.purpose = '';
                    this.otp.purposeName = 'All Purposes';
                    this.otp.status = '';
                    this.clearDateRange('otp');
                    this.clearDateRange('otpExpires', 'otp', 'expiresFrom', 'expiresTo');
                },
                payouts: () => {
                    this.payouts.status = '';
                    this.payouts.method = '';
                },
            };

            resetters[tab]?.();
            this[tab].page = 1;
            this.loadTab(tab);
        },

        loadTab(tab) {
            const loaders = {
                shipments: () => this.loadShipments(),
                packages: () => this.loadPackages(),
                activity: () => this.loadActivityLogs(),
                otp: () => this.loadOtpLogs(),
                payouts: () => this.loadPayouts(),
            };

            loaders[tab]?.();
        },

        activeFilterChips(tab) {
            const tabState = this[tab];
            const chips = [];
            const add = (key, label, value) => {
                if (value !== undefined && value !== null && value !== '') chips.push({ key, label });
            };

            if (tab === 'shipments') {
                add('status', `Status: ${tabState.statusName}`, tabState.status);
                add('dateRange', `Created: ${this.dateRangeLabel(tabState.dateFrom, tabState.dateTo)}`, tabState.dateFrom || tabState.dateTo);
                add('recipientPhone', `Phone: ${tabState.recipientPhone}`, tabState.recipientPhone);
                add('location', `Location: ${tabState.location}`, tabState.location);
                add('packageCount', `Packages: ${this.packageCountLabel(tabState.packageCount)}`, tabState.packageCount);
            } else if (tab === 'packages') {
                add('status', `Status: ${tabState.statusName}`, tabState.status);
                add('dateRange', `Created: ${this.dateRangeLabel(tabState.dateFrom, tabState.dateTo)}`, tabState.dateFrom || tabState.dateTo);
                add('deliveryMethod', `Method: ${this.deliveryMethodLabel(tabState.deliveryMethod)}`, tabState.deliveryMethod);
                add('recipientPhone', `Phone: ${tabState.recipientPhone}`, tabState.recipientPhone);
                add('location', `Location: ${tabState.location}`, tabState.location);
                add('quantityMin', `Min qty: ${tabState.quantityMin}`, tabState.quantityMin);
                add('quantityMax', `Max qty: ${tabState.quantityMax}`, tabState.quantityMax);
            } else if (tab === 'activity') {
                add('action', `Action: ${tabState.actionName}`, tabState.action);
                add('dateRange', `Activity: ${this.dateRangeLabel(tabState.dateFrom, tabState.dateTo)}`, tabState.dateFrom || tabState.dateTo);
                add('deviceType', `Device: ${this.deviceTypeLabel(tabState.deviceType)}`, tabState.deviceType);
                add('ipAddress', `IP: ${tabState.ipAddress}`, tabState.ipAddress);
            } else if (tab === 'otp') {
                add('purpose', `Purpose: ${tabState.purposeName}`, tabState.purpose);
                add('status', `Status: ${this.otpStatusLabel(tabState.status)}`, tabState.status);
                add('dateRange', `Created: ${this.dateRangeLabel(tabState.dateFrom, tabState.dateTo)}`, tabState.dateFrom || tabState.dateTo);
                add('expiresRange', `Expires: ${this.dateRangeLabel(tabState.expiresFrom, tabState.expiresTo)}`, tabState.expiresFrom || tabState.expiresTo);
            } else if (tab === 'payouts') {
                add('status', `Status: ${this.payoutStatusLabel(tabState.status)}`, tabState.status);
                add('method', `Method: ${this.payoutMethodLabel(tabState.method)}`, tabState.method);
            }

            return chips;
        },

        clearFilter(tab, key) {
            const tabState = this[tab];
            const labelResetters = {
                shipments: { status: () => { tabState.statusName = 'All Statuses'; } },
                packages: { status: () => { tabState.statusName = 'All Statuses'; } },
                activity: { action: () => { tabState.actionName = 'All Actions'; } },
                otp: { purpose: () => { tabState.purposeName = 'All Purposes'; } },
            };

            if (key === 'dateRange') {
                this.clearDateRange(tab);
            } else if (key === 'expiresRange' && tab === 'otp') {
                this.clearDateRange('otpExpires', 'otp', 'expiresFrom', 'expiresTo');
            } else {
                tabState[key] = '';
            }
            labelResetters[tab]?.[key]?.();
            tabState.page = 1;
            this.loadTab(tab);
        },

        dateRangeLabel(from, to) {
            return `${from || '...'} - ${to || '...'}`;
        },

        initDateRanges() {
            const setupPicker = () => {
                this.setupDateRangePicker(this.$refs.shipmentsCreatedRange, 'shipments');
                this.setupDateRangePicker(this.$refs.packagesCreatedRange, 'packages');
                this.setupDateRangePicker(this.$refs.activityCreatedRange, 'activity');
                this.setupDateRangePicker(this.$refs.otpCreatedRange, 'otp');
                this.setupDateRangePicker(this.$refs.otpExpiresRange, 'otpExpires', 'otp', 'expiresFrom', 'expiresTo');
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

        setupDateRangePicker(input, refKey, stateKey = refKey, fromField = 'dateFrom', toField = 'dateTo') {
            if (!input || !window.$ || !window.moment || !window.$.fn.daterangepicker) return;
            if (this[`${refKey}RangePicker`]) return;

            const $input = window.$(input);
            $input.daterangepicker({
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                opens: 'left',
                locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
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
                this[stateKey][fromField] = picker.startDate.format('YYYY-MM-DD');
                this[stateKey][toField] = picker.endDate.format('YYYY-MM-DD');
                $input.val(`${this[stateKey][fromField]} - ${this[stateKey][toField]}`);
                this[stateKey].page = 1;
                this.loadTab(stateKey);
            });

            $input.on('cancel.daterangepicker', () => {
                this.clearDateRange(refKey, stateKey, fromField, toField);
                this[stateKey].page = 1;
                this.loadTab(stateKey);
            });

            this[`${refKey}RangePicker`] = $input.data('daterangepicker');
        },

        clearDateRange(refKey, stateKey = refKey, fromField = 'dateFrom', toField = 'dateTo') {
            if (!this[stateKey]) return;
            this[stateKey][fromField] = '';
            this[stateKey][toField] = '';

            const input = {
                shipments: this.$refs.shipmentsCreatedRange,
                packages: this.$refs.packagesCreatedRange,
                activity: this.$refs.activityCreatedRange,
                otp: this.$refs.otpCreatedRange,
                otpExpires: this.$refs.otpExpiresRange,
            }[refKey];

            if (input && window.$) {
                window.$(input).val('');
            }
        },

        packageCountLabel(value) {
            return ({ one: 'One package', multiple: 'Multiple packages' })[value] || value;
        },

        deliveryMethodLabel(value) {
            return ({ direct: 'Recipient delivery', bus_handoff: 'Bus handoff', pickup: 'Self pickup' })[value] || value;
        },

        deviceTypeLabel(value) {
            return ({ mobile: 'Mobile', web: 'Web', desktop: 'Desktop' })[value] || value;
        },

        otpStatusLabel(value) {
            return ({ verified: 'Verified', expired: 'Expired', pending: 'Pending' })[value] || value;
        },

        payoutMethodLabel(value) {
            return ({ momo: 'MOMO', bank: 'Bank', cash: 'Cash' })[value] || value;
        },

        // ── Pagination helpers ──────────────────────────────────────
        shipmentsFirstPage()    { this.shipments.page = 1; this.loadShipments(); },
        shipmentsPrevPage()     { if (this.shipments.page > 1) { this.shipments.page--; this.loadShipments(); } },
        shipmentsNextPage()     { if (this.shipments.page < this.shipments.meta.last_page) { this.shipments.page++; this.loadShipments(); } },
        shipmentsLastPage()     { this.shipments.page = this.shipments.meta.last_page; this.loadShipments(); },
        setShipmentsPerPage(n)  { this.shipments.perPage = n; this.shipments.page = 1; this.loadShipments(); },

        packagesPrevPage()      { if (this.packages.page > 1) { this.packages.page--; this.loadPackages(); } },
        packagesNextPage()      { if (this.packages.page < this.packages.meta.last_page) { this.packages.page++; this.loadPackages(); } },

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

        payoutsPrevPage()       { if (this.payouts.page > 1) { this.payouts.page--; this.loadPayouts(); } },
        payoutsNextPage()       { if (this.payouts.page < this.payouts.meta.last_page) { this.payouts.page++; this.loadPayouts(); } },

        // ── Data loaders ──────────────────────────────────────────
        async loadShipments() {
            this.shipments.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.shipments.page,
                    per_page: this.shipments.perPage,
                    search: this.shipments.search,
                    status: this.shipments.status,
                    date_from: this.shipments.dateFrom,
                    date_to: this.shipments.dateTo,
                    recipient_phone: this.shipments.recipientPhone,
                    location: this.shipments.location,
                    package_count: this.shipments.packageCount,
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

        async loadPackages() {
            this.packages.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.packages.page,
                    per_page: this.packages.perPage,
                    search: this.packages.search,
                    status: this.packages.status,
                    date_from: this.packages.dateFrom,
                    date_to: this.packages.dateTo,
                    delivery_method: this.packages.deliveryMethod,
                    recipient_phone: this.packages.recipientPhone,
                    location: this.packages.location,
                    quantity_min: this.packages.quantityMin,
                    quantity_max: this.packages.quantityMax,
                    sort: this.packages.sortBy,
                    direction: this.packages.sortDirection,
                });

                const response = await fetch(`${this.config.packagesEndpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                this.packages.data = data.data || [];
                this.packages.meta = data.meta || this.packages.meta;
            } catch (error) {
                console.error('Failed to load packages:', error);
            } finally {
                this.packages.loading = false;
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
                    date_from: this.activity.dateFrom,
                    date_to: this.activity.dateTo,
                    device_type: this.activity.deviceType,
                    ip_address: this.activity.ipAddress,
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
                    status: this.otp.status,
                    date_from: this.otp.dateFrom,
                    date_to: this.otp.dateTo,
                    expires_from: this.otp.expiresFrom,
                    expires_to: this.otp.expiresTo,
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

        async loadPayouts() {
            this.payouts.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.payouts.page,
                    per_page: this.payouts.perPage,
                    search: this.payouts.search,
                    status: this.payouts.status,
                    method: this.payouts.method,
                });

                const response = await fetch(`${this.config.payoutsEndpoint}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                this.payouts.data = data.data || [];
                this.payouts.meta = data.meta || this.payouts.meta;
            } catch (error) {
                console.error('Failed to load payouts:', error);
            } finally {
                this.payouts.loading = false;
            }
        },

        openPayoutModal() {
            if (!this.payouts.summary.can_request_payout) return;
            if (!this.payouts.summary.payout_account?.is_set) {
                if (window.showToast) window.showToast('Set the vendor MoMo payout account before processing payout.', 'error');
                return;
            }
            this.payoutForm = {
                amount: this.payouts.summary.available_balance || '',
                payment_method: 'momo',
                payment_phone: this.payouts.summary.payout_account.account_number || '',
                payment_reference: '',
                notes: '',
                confirm_immediately: true,
            };
            this.showPayoutModal = true;
        },

        closePayoutModal() {
            this.showPayoutModal = false;
            this.payoutSaving = false;
        },

        async submitPayout() {
            this.payoutSaving = true;
            try {
                const response = await fetch(this.config.createPayoutEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.payoutForm),
                });
                const data = await response.json();
                if (!response.ok || data.success === false) throw new Error(data.message || 'Failed to create payout');
                if (window.showToast) window.showToast(data.message || 'Vendor paid successfully.', 'success');
                this.closePayoutModal();
                await this.refreshPayoutSummary();
                this.loadPayouts();
            } catch (error) {
                if (window.showToast) window.showToast(error.message || 'Failed to create payout.', 'error');
            } finally {
                this.payoutSaving = false;
            }
        },

        async refreshPayoutSummary() {
            window.location.reload();
        },

        openMarkSentModal(payout) {
            this.markSentPayout = payout;
            this.markSentForm.payment_reference = payout.payment_reference || '';
            this.showMarkSentModal = true;
        },

        async submitMarkSent() {
            if (!this.markSentPayout) return;
            this.markSentSaving = true;
            try {
                const response = await fetch(this.config.markPayoutSentEndpoint.replace('__PAYOUT__', this.markSentPayout.id), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.markSentForm),
                });
                const data = await response.json();
                if (!response.ok || data.success === false) throw new Error(data.message || 'Failed to mark payout as sent');
                if (window.showToast) window.showToast(data.message || 'Payout marked as sent.', 'success');
                this.showMarkSentModal = false;
                this.loadPayouts();
            } catch (error) {
                if (window.showToast) window.showToast(error.message || 'Failed to mark payout as sent.', 'error');
            } finally {
                this.markSentSaving = false;
            }
        },

        openConfirmPayoutModal(payout) {
            this.confirmPayoutTarget = payout;
            this.showConfirmPayoutModal = true;
        },

        closeConfirmPayoutModal() {
            if (this.confirmPayoutSaving) return;
            this.showConfirmPayoutModal = false;
            this.confirmPayoutTarget = null;
        },

        async submitConfirmPayout() {
            if (!this.confirmPayoutTarget) return;
            this.confirmPayoutSaving = true;

            try {
                const response = await fetch(this.config.confirmPayoutEndpoint.replace('__PAYOUT__', this.confirmPayoutTarget.id), {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();
                if (!response.ok || data.success === false) throw new Error(data.message || 'Failed to confirm payout');
                if (window.showToast) window.showToast(data.message || 'Payout confirmed.', 'success');
                this.closeConfirmPayoutModal();
                this.loadPayouts();
            } catch (error) {
                if (window.showToast) window.showToast(error.message || 'Failed to confirm payout.', 'error');
            } finally {
                this.confirmPayoutSaving = false;
            }
        },

        payoutStatusLabel(status) {
            return ({ pending: 'Pending', sent: 'Sent', confirmed: 'Confirmed' })[status] || status;
        },

        payoutStatusClass(status) {
            return {
                pending: 'bg-amber-100 text-amber-700',
                sent: 'bg-blue-100 text-blue-700',
                confirmed: 'bg-emerald-100 text-emerald-700',
            }[status] || 'bg-slate-100 text-slate-600';
        },

        payoutNetworkLabel(network) {
            return ({ mtn: 'MTN MoMo', telecel: 'Telecel Cash', airteltigo: 'AirtelTigo Money' })[network] || '-';
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
                is_active: this.vendor.is_active,
                commission_rate_override: this.vendor.commission_rate_override ?? '',
                payout_momo_network: this.vendor.payout_momo_network || this.vendor.payout_account?.network || '',
                payout_account_name: this.vendor.payout_account_name || this.vendor.payout_account?.account_name || '',
                payout_account_number: this.vendor.payout_account_number || this.vendor.payout_account?.account_number || ''
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
                this.vendor.phone = data.vendor?.phone || this.form.phone;
                this.vendor.is_active = this.form.is_active;
                this.vendor.commission_rate_override = this.form.commission_rate_override === '' ? null : this.form.commission_rate_override;
                this.vendor.payout_momo_network = this.form.payout_momo_network || null;
                this.vendor.payout_account_name = this.form.payout_account_name || null;
                this.vendor.payout_account_number = data.vendor?.payout_account_number || this.form.payout_account_number || null;
                this.vendor.payout_account = data.vendor?.payout_account || null;
                this.payouts.summary.payout_account = data.vendor?.payout_account || this.payouts.summary.payout_account;

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
        },

        formatMoney(value) {
            const amount = Number(value || 0);
            return amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        statusBadgeClass(status) {
            if (status === 'delivered') return 'bg-emerald-100 text-emerald-700';
            if (['in_transit', 'out_for_delivery', 'at_destination'].includes(status)) return 'bg-amber-100 text-amber-700';
            if (['picked_up', 'at_warehouse', 'sorted', 'handed_to_courier'].includes(status)) return 'bg-violet-100 text-violet-700';
            if (status === 'returned') return 'bg-rose-100 text-rose-700';
            return 'bg-slate-100 text-slate-600';
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
