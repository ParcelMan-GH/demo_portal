/**
 * Admin driver show page Alpine component
 * Extracted from Blade inline scripts and bundled via Vite.
 */

function driverShow() {
    return {
        config: {},
        driver: {},
        canManage: false,

        activeTab: 'pickups',
        showToggleModal: false,

        // Pickups state
        pickups: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            showFilters: false,
            search: '',
            status: '',
            statusName: 'All Statuses',
            dateFrom: '',
            dateTo: '',
            vendor: '',
            recipientPhone: '',
            page: 1,
            perPage: 10,
            sortBy: 'created_at',
            sortDirection: 'desc',
            columns: [
                { key: 'shipment_number', label: 'Shipment #' },
                { key: 'vendor_name', label: 'Vendor' },
                { key: 'status', label: 'Status' },
                { key: 'assigned_at', label: 'Assigned At' },
                { key: 'completed_at', label: 'Completed At' },
            ],
            visibleColumns: {
                shipment_number: true,
                vendor_name: true,
                status: true,
                assigned_at: true,
                completed_at: true,
            },
        },

        // Transports state
        transports: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            showFilters: false,
            search: '',
            status: '',
            statusName: 'All Statuses',
            dateFrom: '',
            dateTo: '',
            origin: '',
            destination: '',
            receivedFrom: '',
            receivedTo: '',
            page: 1,
            perPage: 10,
            sortBy: 'created_at',
            sortDirection: 'desc',
            columns: [
                { key: 'manifest_number', label: 'Manifest #' },
                { key: 'origin_warehouse', label: 'Origin' },
                { key: 'destination_warehouse', label: 'Destination' },
                { key: 'status', label: 'Status' },
                { key: 'assigned_at', label: 'Assigned At' },
                { key: 'received_at', label: 'Received At' },
            ],
            visibleColumns: {
                manifest_number: true,
                origin_warehouse: true,
                destination_warehouse: true,
                status: true,
                assigned_at: true,
                received_at: true,
            },
        },

        // Deliveries state
        deliveries: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            showFilters: false,
            search: '',
            status: '',
            statusName: 'All Statuses',
            dateFrom: '',
            dateTo: '',
            warehouse: '',
            stopsMin: '',
            stopsMax: '',
            completedFrom: '',
            completedTo: '',
            page: 1,
            perPage: 10,
            sortBy: 'created_at',
            sortDirection: 'desc',
            columns: [
                { key: 'run_number', label: 'Run #' },
                { key: 'warehouse', label: 'Warehouse' },
                { key: 'stops_count', label: 'Stops' },
                { key: 'status', label: 'Status' },
                { key: 'assigned_at', label: 'Assigned At' },
                { key: 'completed_at', label: 'Completed At' },
            ],
            visibleColumns: {
                run_number: true,
                warehouse: true,
                stops_count: true,
                status: true,
                assigned_at: true,
                completed_at: true,
            },
        },

        // Packages state
        packagesLoaded: false,
        packages: {
            rows: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            filter: 'current',
            showFilters: false,
            search: '',
            eventType: '',
            eventTypeName: 'All States',
            dateFrom: '',
            dateTo: '',
            recipient: '',
            location: '',
            page: 1,
            perPage: 10,
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

        // Edit modal state
        showEditModal: false,
        saving: false,
        toggling: false,
        errors: {},
        form: {
            name: '',
            email: '',
            phone: '',
            password: '',
            vehicle_type: '',
            vehicle_number: '',
            license_number: '',
            task_capabilities: [],
            is_active: true
        },

        init() {
            this.config = window.driverShowConfig;
            this.driver = this.config.driver;
            this.canManage = this.config.canManage;
            this.setActiveTab(this.validTabFromUrl(), { replace: true });
            window.addEventListener('popstate', () => {
                this.setActiveTab(this.validTabFromUrl(), { replace: true });
            });
            this.$nextTick(() => this.initDateRanges());
        },

        validTabFromUrl() {
            const allowed = ['pickups', 'transports', 'deliveries', 'activity', 'packages'];
            const tab = new URLSearchParams(window.location.search).get('tab') || 'pickups';
            return allowed.includes(tab) ? tab : 'pickups';
        },

        setActiveTab(tab, options = {}) {
            this.activeTab = tab;

            if (tab === 'pickups' && !this.pickups.data.length) this.loadPickups();
            if (tab === 'transports' && !this.transports.data.length) this.loadTransports();
            if (tab === 'deliveries' && !this.deliveries.data.length) this.loadDeliveries();
            if (tab === 'activity' && !this.activity.data.length) this.loadActivityLogs();
            if (tab === 'packages' && !this.packagesLoaded) this.loadPackages();

            const url = new URL(window.location.href);
            if (tab === 'pickups') {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', tab);
            }

            const method = options.replace ? 'replaceState' : 'pushState';
            window.history[method]({}, '', url);
        },

        loadTab(tab) {
            const loaders = {
                pickups: () => this.loadPickups(),
                transports: () => this.loadTransports(),
                deliveries: () => this.loadDeliveries(),
                activity: () => this.loadActivityLogs(),
                packages: () => this.loadPackages(),
            };
            loaders[tab]?.();
        },

        applyTabFilters(tab) {
            this[tab].page = 1;
            this.loadTab(tab);
        },

        clearTabFilters(tab) {
            const resetters = {
                pickups: () => {
                    this.pickups.status = '';
                    this.pickups.statusName = 'All Statuses';
                    this.clearDateRange('pickups');
                    this.pickups.vendor = '';
                    this.pickups.recipientPhone = '';
                },
                transports: () => {
                    this.transports.status = '';
                    this.transports.statusName = 'All Statuses';
                    this.clearDateRange('transports');
                    this.clearDateRange('transportsReceived', 'transports', 'receivedFrom', 'receivedTo');
                    this.transports.origin = '';
                    this.transports.destination = '';
                },
                deliveries: () => {
                    this.deliveries.status = '';
                    this.deliveries.statusName = 'All Statuses';
                    this.clearDateRange('deliveries');
                    this.clearDateRange('deliveriesCompleted', 'deliveries', 'completedFrom', 'completedTo');
                    this.deliveries.warehouse = '';
                    this.deliveries.stopsMin = '';
                    this.deliveries.stopsMax = '';
                },
                packages: () => {
                    this.packages.filter = 'current';
                    this.packages.eventType = '';
                    this.packages.eventTypeName = 'All States';
                    this.clearDateRange('packages');
                    this.packages.recipient = '';
                    this.packages.location = '';
                },
                activity: () => {
                    this.activity.action = '';
                    this.activity.actionName = 'All Actions';
                    this.clearDateRange('activity');
                    this.activity.deviceType = '';
                    this.activity.ipAddress = '';
                },
            };

            resetters[tab]?.();
            this[tab].page = 1;
            this.loadTab(tab);
        },

        activeFilterChips(tab) {
            const state = this[tab];
            const chips = [];
            const add = (key, label, value) => {
                if (value !== undefined && value !== null && value !== '') chips.push({ key, label });
            };

            if (tab === 'pickups') {
                add('status', `Status: ${state.statusName}`, state.status);
                add('dateRange', `Assigned: ${this.dateRangeLabel(state.dateFrom, state.dateTo)}`, state.dateFrom || state.dateTo);
                add('vendor', `Vendor: ${state.vendor}`, state.vendor);
                add('recipientPhone', `Phone: ${state.recipientPhone}`, state.recipientPhone);
            } else if (tab === 'transports') {
                add('status', `Status: ${state.statusName}`, state.status);
                add('dateRange', `Assigned: ${this.dateRangeLabel(state.dateFrom, state.dateTo)}`, state.dateFrom || state.dateTo);
                add('receivedRange', `Received: ${this.dateRangeLabel(state.receivedFrom, state.receivedTo)}`, state.receivedFrom || state.receivedTo);
                add('origin', `Origin: ${state.origin}`, state.origin);
                add('destination', `Destination: ${state.destination}`, state.destination);
            } else if (tab === 'deliveries') {
                add('status', `Status: ${state.statusName}`, state.status);
                add('dateRange', `Assigned: ${this.dateRangeLabel(state.dateFrom, state.dateTo)}`, state.dateFrom || state.dateTo);
                add('completedRange', `Completed: ${this.dateRangeLabel(state.completedFrom, state.completedTo)}`, state.completedFrom || state.completedTo);
                add('warehouse', `Warehouse: ${state.warehouse}`, state.warehouse);
                add('stopsMin', `Min stops: ${state.stopsMin}`, state.stopsMin);
                add('stopsMax', `Max stops: ${state.stopsMax}`, state.stopsMax);
            } else if (tab === 'packages') {
                add('filter', `Scope: ${state.filter === 'current' ? 'Current' : 'All history'}`, state.filter !== 'current' ? state.filter : '');
                add('eventType', `State: ${state.eventTypeName}`, state.eventType);
                add('dateRange', `Date: ${this.dateRangeLabel(state.dateFrom, state.dateTo)}`, state.dateFrom || state.dateTo);
                add('recipient', `Recipient: ${state.recipient}`, state.recipient);
                add('location', `Location: ${state.location}`, state.location);
            } else if (tab === 'activity') {
                add('action', `Action: ${state.actionName}`, state.action);
                add('dateRange', `Activity: ${this.dateRangeLabel(state.dateFrom, state.dateTo)}`, state.dateFrom || state.dateTo);
                add('deviceType', `Device: ${this.deviceTypeLabel(state.deviceType)}`, state.deviceType);
                add('ipAddress', `IP: ${state.ipAddress}`, state.ipAddress);
            }

            return chips;
        },

        clearFilter(tab, key) {
            if (key === 'dateRange') this.clearDateRange(tab);
            else if (key === 'receivedRange') this.clearDateRange('transportsReceived', 'transports', 'receivedFrom', 'receivedTo');
            else if (key === 'completedRange') this.clearDateRange('deliveriesCompleted', 'deliveries', 'completedFrom', 'completedTo');
            else if (key === 'status') {
                this[tab].status = '';
                this[tab].statusName = 'All Statuses';
            } else if (key === 'action') {
                this.activity.action = '';
                this.activity.actionName = 'All Actions';
            } else if (key === 'eventType') {
                this.packages.eventType = '';
                this.packages.eventTypeName = 'All States';
            } else {
                this[tab][key] = key === 'filter' ? 'current' : '';
            }
            this[tab].page = 1;
            this.loadTab(tab);
        },

        dateRangeLabel(from, to) {
            return `${from || '...'} - ${to || '...'}`;
        },

        initDateRanges() {
            const setupPicker = () => {
                this.setupDateRangePicker(this.$refs.pickupsAssignedRange, 'pickups');
                this.setupDateRangePicker(this.$refs.transportsAssignedRange, 'transports');
                this.setupDateRangePicker(this.$refs.transportsReceivedRange, 'transportsReceived', 'transports', 'receivedFrom', 'receivedTo');
                this.setupDateRangePicker(this.$refs.deliveriesAssignedRange, 'deliveries');
                this.setupDateRangePicker(this.$refs.deliveriesCompletedRange, 'deliveriesCompleted', 'deliveries', 'completedFrom', 'completedTo');
                this.setupDateRangePicker(this.$refs.packagesEventRange, 'packages');
                this.setupDateRangePicker(this.$refs.activityCreatedRange, 'activity');
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            if (!document.getElementById('daterangepicker-css')) {
                const link = document.createElement('link');
                link.id = 'daterangepicker-css';
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
                pickups: this.$refs.pickupsAssignedRange,
                transports: this.$refs.transportsAssignedRange,
                transportsReceived: this.$refs.transportsReceivedRange,
                deliveries: this.$refs.deliveriesAssignedRange,
                deliveriesCompleted: this.$refs.deliveriesCompletedRange,
                packages: this.$refs.packagesEventRange,
                activity: this.$refs.activityCreatedRange,
            }[refKey];

            if (input && window.$) window.$(input).val('');
        },

        deviceTypeLabel(value) {
            return ({ mobile: 'Mobile', web: 'Web', desktop: 'Desktop' })[value] || value;
        },

        // ── Sort helpers ──────────────────────────────────────────
        sortPickups(column) {
            if (this.pickups.sortBy === column) {
                this.pickups.sortDirection = this.pickups.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.pickups.sortBy = column;
                this.pickups.sortDirection = 'asc';
            }
            this.pickups.page = 1;
            this.loadPickups();
        },

        sortTransports(column) {
            if (this.transports.sortBy === column) {
                this.transports.sortDirection = this.transports.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.transports.sortBy = column;
                this.transports.sortDirection = 'asc';
            }
            this.transports.page = 1;
            this.loadTransports();
        },

        sortDeliveries(column) {
            if (this.deliveries.sortBy === column) {
                this.deliveries.sortDirection = this.deliveries.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.deliveries.sortBy = column;
                this.deliveries.sortDirection = 'asc';
            }
            this.deliveries.page = 1;
            this.loadDeliveries();
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

        // ── Column visibility helpers ──────────────────────────────
        togglePickupsColumn(key) { this.pickups.visibleColumns[key] = !this.pickups.visibleColumns[key]; },
        toggleTransportsColumn(key) { this.transports.visibleColumns[key] = !this.transports.visibleColumns[key]; },
        toggleDeliveriesColumn(key) { this.deliveries.visibleColumns[key] = !this.deliveries.visibleColumns[key]; },
        toggleActivityColumn(key) { this.activity.visibleColumns[key] = !this.activity.visibleColumns[key]; },

        visibleColumnCount(tab) {
            return Object.values(this[tab].visibleColumns).filter(Boolean).length;
        },

        // ── Pagination helpers ──────────────────────────────────────
        pickupsFirstPage()      { this.pickups.page = 1; this.loadPickups(); },
        pickupsPrevPage()       { if (this.pickups.page > 1) { this.pickups.page--; this.loadPickups(); } },
        pickupsNextPage()       { if (this.pickups.page < this.pickups.meta.last_page) { this.pickups.page++; this.loadPickups(); } },
        pickupsLastPage()       { this.pickups.page = this.pickups.meta.last_page; this.loadPickups(); },
        setPickupsPerPage(n)    { this.pickups.perPage = n; this.pickups.page = 1; this.loadPickups(); },

        transportsFirstPage()   { this.transports.page = 1; this.loadTransports(); },
        transportsPrevPage()    { if (this.transports.page > 1) { this.transports.page--; this.loadTransports(); } },
        transportsNextPage()    { if (this.transports.page < this.transports.meta.last_page) { this.transports.page++; this.loadTransports(); } },
        transportsLastPage()    { this.transports.page = this.transports.meta.last_page; this.loadTransports(); },
        setTransportsPerPage(n) { this.transports.perPage = n; this.transports.page = 1; this.loadTransports(); },

        deliveriesFirstPage()   { this.deliveries.page = 1; this.loadDeliveries(); },
        deliveriesPrevPage()    { if (this.deliveries.page > 1) { this.deliveries.page--; this.loadDeliveries(); } },
        deliveriesNextPage()    { if (this.deliveries.page < this.deliveries.meta.last_page) { this.deliveries.page++; this.loadDeliveries(); } },
        deliveriesLastPage()    { this.deliveries.page = this.deliveries.meta.last_page; this.loadDeliveries(); },
        setDeliveriesPerPage(n) { this.deliveries.perPage = n; this.deliveries.page = 1; this.loadDeliveries(); },

        activityFirstPage()     { this.activity.page = 1; this.loadActivityLogs(); },
        activityPrevPage()      { if (this.activity.page > 1) { this.activity.page--; this.loadActivityLogs(); } },
        activityNextPage()      { if (this.activity.page < this.activity.meta.last_page) { this.activity.page++; this.loadActivityLogs(); } },
        activityLastPage()      { this.activity.page = this.activity.meta.last_page; this.loadActivityLogs(); },
        setActivityPerPage(n)   { this.activity.perPage = n; this.activity.page = 1; this.loadActivityLogs(); },

        packagesPrevPage()      { if (this.packages.page > 1) { this.packages.page--; this.loadPackages(); } },
        packagesNextPage()      { if (this.packages.page < this.packages.meta.last_page) { this.packages.page++; this.loadPackages(); } },
        setPackagesPerPage(n)   { this.packages.perPage = n; this.packages.page = 1; this.loadPackages(); },

        // ── Data loaders ──────────────────────────────────────────
        async loadPickups() {
            this.pickups.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pickups.page,
                    per_page: this.pickups.perPage,
                    search: this.pickups.search,
                    status: this.pickups.status,
                    date_from: this.pickups.dateFrom,
                    date_to: this.pickups.dateTo,
                    vendor: this.pickups.vendor,
                    recipient_phone: this.pickups.recipientPhone,
                    sort: this.pickups.sortBy,
                    direction: this.pickups.sortDirection,
                });

                const response = await fetch(`${this.config.pickupsEndpoint}?${params}`);
                const data = await response.json();

                this.pickups.data = data.data;
                this.pickups.meta = data.meta;
            } catch (error) {
                console.error('Failed to load pickups:', error);
            } finally {
                this.pickups.loading = false;
            }
        },

        async loadTransports() {
            this.transports.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.transports.page,
                    per_page: this.transports.perPage,
                    search: this.transports.search,
                    status: this.transports.status,
                    date_from: this.transports.dateFrom,
                    date_to: this.transports.dateTo,
                    received_from: this.transports.receivedFrom,
                    received_to: this.transports.receivedTo,
                    origin: this.transports.origin,
                    destination: this.transports.destination,
                    sort: this.transports.sortBy,
                    direction: this.transports.sortDirection,
                });

                const response = await fetch(`${this.config.transportManifestsEndpoint}?${params}`);
                const data = await response.json();

                this.transports.data = data.data;
                this.transports.meta = data.meta;
            } catch (error) {
                console.error('Failed to load transport manifests:', error);
            } finally {
                this.transports.loading = false;
            }
        },

        async loadDeliveries() {
            this.deliveries.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.deliveries.page,
                    per_page: this.deliveries.perPage,
                    search: this.deliveries.search,
                    status: this.deliveries.status,
                    date_from: this.deliveries.dateFrom,
                    date_to: this.deliveries.dateTo,
                    completed_from: this.deliveries.completedFrom,
                    completed_to: this.deliveries.completedTo,
                    warehouse: this.deliveries.warehouse,
                    stops_min: this.deliveries.stopsMin,
                    stops_max: this.deliveries.stopsMax,
                    sort: this.deliveries.sortBy,
                    direction: this.deliveries.sortDirection,
                });

                const response = await fetch(`${this.config.deliveryRunsEndpoint}?${params}`);
                const data = await response.json();

                this.deliveries.data = data.data;
                this.deliveries.meta = data.meta;
            } catch (error) {
                console.error('Failed to load delivery runs:', error);
            } finally {
                this.deliveries.loading = false;
            }
        },

        async loadPackages() {
            this.packages.loading = true;
            this.packagesLoaded = true;
            try {
                const params = new URLSearchParams();
                params.set('page', this.packages.page);
                params.set('filter', this.packages.filter);
                params.set('search', this.packages.search);
                params.set('event_type', this.packages.eventType);
                params.set('date_from', this.packages.dateFrom);
                params.set('date_to', this.packages.dateTo);
                params.set('recipient', this.packages.recipient);
                params.set('location', this.packages.location);
                params.set('per_page', this.packages.perPage);
                const res = await fetch(this.config.packagesDataUrl + '?' + params, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.packages.rows = json.data || [];
                this.packages.meta = json.meta || this.packages.meta;
            } catch (e) { console.error('Failed to load packages', e); }
            this.packages.loading = false;
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

        // ── Export helpers ──────────────────────────────────────────
        printTable(tab) {
            const tabData = this[tab];
            if (!tabData.data.length) return;

            const printWindow = window.open('', '_blank');
            if (!printWindow) return;
            const doc = printWindow.document;

            const titles = { pickups: 'Pickups', transports: 'Transport Manifests', deliveries: 'Delivery Runs', activity: 'Activity Logs' };

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

            const titles = { pickups: 'pickups', transports: 'transport_manifests', deliveries: 'delivery_runs', activity: 'activity_logs' };
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
                name: this.driver.name,
                email: this.driver.email,
                phone: this.driver.phone,
                password: '',
                vehicle_type: this.driver.vehicle_type || '',
                vehicle_number: this.driver.vehicle_number || '',
                license_number: this.driver.license_number || '',
                task_capabilities: Array.isArray(this.driver.task_capabilities) ? [...this.driver.task_capabilities] : [],
                is_active: this.driver.is_active
            };
            this.errors = {};
            this.showEditModal = true;
        },

        async saveDriver() {
            this.saving = true;
            this.errors = {};

            try {
                const payload = { ...this.form };
                if (!payload.password) {
                    delete payload.password;
                }

                const response = await fetch(this.config.updateEndpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        this.errors = data.errors || {};
                    } else {
                        throw new Error(data.message || 'Failed to update driver');
                    }
                    return;
                }

                const updatedDriver = data.driver || {};

                // Keep the detail view aligned with server-normalized values.
                this.driver.name = updatedDriver.name || this.form.name;
                this.driver.email = updatedDriver.email || this.form.email;
                this.driver.phone = updatedDriver.phone || this.form.phone;
                this.driver.vehicle_type = updatedDriver.vehicle_type ?? this.form.vehicle_type;
                this.driver.vehicle_number = updatedDriver.vehicle_number ?? this.form.vehicle_number;
                this.driver.license_number = updatedDriver.license_number ?? this.form.license_number;
                this.driver.task_capabilities = Array.isArray(updatedDriver.task_capabilities)
                    ? [...updatedDriver.task_capabilities]
                    : [...this.form.task_capabilities];
                this.driver.is_active = typeof updatedDriver.is_active === 'boolean'
                    ? updatedDriver.is_active
                    : this.form.is_active;

                this.showEditModal = false;

                // Show success notification
                if (window.showToast) {
                    window.showToast('Driver updated successfully', 'success');
                }
            } catch (error) {
                console.error('Save error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to update driver', 'error');
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

                this.driver.is_active = data.is_active;
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
        }
    };
}

function getDriverShowConfig() {
    const container = document.querySelector('[data-driver-show-config]');
    if (!container) return null;

    const rawConfig = container.getAttribute('data-driver-show-config');
    if (!rawConfig) return null;

    try {
        return JSON.parse(rawConfig);
    } catch (error) {
        console.error('Invalid driver show config JSON:', error);
        return null;
    }
}

function registerDriverShowPage() {
    if (!window.Alpine) return;

    const config = getDriverShowConfig();
    if (!config) return;

    window.driverShowConfig = config;
    Alpine.data('driverShow', driverShow);
}

if (window.Alpine) {
    registerDriverShowPage();
} else {
    document.addEventListener('alpine:init', registerDriverShowPage);
}
