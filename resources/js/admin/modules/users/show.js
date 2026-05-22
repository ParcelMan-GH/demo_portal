function ensureUserDetailDateRangeDependencies() {
    const cssId = 'daterangepicker-css';
    if (!document.getElementById(cssId)) {
        const link = document.createElement('link');
        link.id = cssId;
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
        document.head.appendChild(link);
    }

    const loadScript = (id, src) => new Promise((resolve) => {
        if (document.getElementById(id)) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.id = id;
        script.src = src;
        script.onload = () => resolve();
        document.body.appendChild(script);
    });

    return loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
        .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
        .then(() => {
            window.$ = window.jQuery = window.jQuery || window.$;
            window.moment = window.moment || moment;
            return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
        });
}

function notify(message, type = 'info') {
    if (window.showToast) {
        window.showToast(message, type);
        return;
    }

    if (type === 'error') {
        console.error(message);
        return;
    }

    console.info(message);
}

document.addEventListener('alpine:init', () => {
    Alpine.data('userShow', () => ({
        config: {},
        user: {},
        tabs: [],
        activeTab: 'overview',
        overview: {
            summary: {},
            counts: {},
            recent: [],
        },
        overviewLoading: false,
        tabStates: {},
        dateRangePicker: null,

        showModal: false,
        modalMode: 'edit',
        editingUser: null,
        submitting: false,
        changePassword: false,
        formData: {
            name: '',
            email: '',
            phone: '',
            photo: null,
            photo_preview_url: '',
            warehouse_id: '',
            role_id: '',
            is_active: '1',
            password: '',
            password_confirmation: '',
        },
        formErrors: {},
        showStatusModal: false,
        statusSubmitting: false,
        showImpersonationModal: false,
        impersonationSubmitting: false,

        init() {
            try {
                this.config = JSON.parse(this.$el.dataset.userShowConfig || '{}');
            } catch (error) {
                notify('User dashboard configuration is invalid.', 'error');
                return;
            }

            this.user = this.config.user || {};
            this.tabs = this.config.tabs || [];
            this.tabs.forEach((tab) => {
                this.tabStates[tab.key] = this.defaultTabState();
            });

            const requestedTab = new URLSearchParams(window.location.search).get('tab');
            this.activeTab = this.tabs.some((tab) => tab.key === requestedTab) ? requestedTab : 'overview';

            this.$nextTick(() => {
                this.loadActiveTab();
                this.initDateRange();
            });
        },

        defaultTabState() {
            return {
                rows: [],
                loading: false,
                filtersOpen: false,
                search: '',
                status: '',
                action: '',
                dateFrom: '',
                dateTo: '',
                perPage: 10,
                sort: 'date',
                direction: 'desc',
                density: 'comfortable',
                columns: [
                    { key: 'date', label: 'Date' },
                    { key: 'module', label: 'Module' },
                    { key: 'reference', label: 'Reference' },
                    { key: 'action', label: 'Action' },
                    { key: 'status', label: 'Status' },
                    { key: 'details', label: 'Details' },
                    { key: 'warehouse', label: 'Warehouse' },
                ],
                visibleColumns: {
                    date: true,
                    module: true,
                    reference: true,
                    action: true,
                    status: true,
                    details: true,
                    warehouse: true,
                },
                meta: {
                    current_page: 1,
                    from: 0,
                    to: 0,
                    total: 0,
                    last_page: 1,
                },
            };
        },

        currentTab() {
            return this.tabs.find((tab) => tab.key === this.activeTab) || this.tabs[0] || {};
        },

        currentState() {
            if (!this.tabStates[this.activeTab]) {
                this.tabStates[this.activeTab] = this.defaultTabState();
            }

            return this.tabStates[this.activeTab];
        },

        setActiveTab(tabKey) {
            if (!this.tabs.some((tab) => tab.key === tabKey)) return;

            this.activeTab = tabKey;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url.toString());

            this.$nextTick(() => {
                this.loadActiveTab();
                this.initDateRange();
            });
        },

        loadActiveTab() {
            if (this.activeTab === 'overview') {
                this.loadOverview();
                return;
            }

            if (!this.currentState().rows.length) {
                this.loadCurrentTab(1);
            }
        },

        async loadOverview() {
            const tab = this.currentTab();
            if (!tab.endpoint) return;

            this.overviewLoading = true;
            try {
                const response = await fetch(tab.endpoint, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to load overview.');

                const result = await response.json();
                this.overview = {
                    summary: result.summary || {},
                    counts: result.counts || {},
                    recent: result.recent || [],
                };
            } catch (error) {
                console.error(error);
                notify('Failed to load user overview.', 'error');
            } finally {
                this.overviewLoading = false;
            }
        },

        async loadCurrentTab(page = null) {
            const tab = this.currentTab();
            const state = this.currentState();
            if (!tab.endpoint) return;

            if (page) {
                state.meta.current_page = page;
            }

            state.loading = true;
            try {
                const params = new URLSearchParams({
                    page: state.meta.current_page || 1,
                    per_page: state.perPage,
                    sort: state.sort,
                    direction: state.direction,
                });

                if (state.search) params.append('search', state.search);
                if (state.status) params.append('status', state.status);
                if (state.action) params.append('action', state.action);
                if (state.dateFrom) params.append('date_from', state.dateFrom);
                if (state.dateTo) params.append('date_to', state.dateTo);

                const response = await fetch(`${tab.endpoint}?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('Failed to load tab data.');

                const result = await response.json();
                state.rows = result.data || [];
                state.meta = {
                    current_page: result.meta?.current_page || 1,
                    from: result.meta?.from || 0,
                    to: result.meta?.to || 0,
                    total: result.meta?.total || 0,
                    last_page: result.meta?.last_page || 1,
                };
            } catch (error) {
                console.error(error);
                notify(`Failed to load ${tab.label || 'activity'}.`, 'error');
            } finally {
                state.loading = false;
            }
        },

        previousPage() {
            const state = this.currentState();
            if (state.meta.current_page <= 1) return;
            state.meta.current_page -= 1;
            this.loadCurrentTab();
        },

        nextPage() {
            const state = this.currentState();
            if (state.meta.current_page >= state.meta.last_page) return;
            state.meta.current_page += 1;
            this.loadCurrentTab();
        },

        paginationLabel() {
            const meta = this.currentState().meta;
            if (!meta.total) return 'Showing 0 records';
            return `Showing ${meta.from} to ${meta.to} of ${meta.total}`;
        },

        clearFilters() {
            const state = this.currentState();
            state.search = '';
            state.status = '';
            state.action = '';
            state.dateFrom = '';
            state.dateTo = '';
            if (this.$refs.dateRangeInput) {
                this.$refs.dateRangeInput.value = '';
            }
            this.loadCurrentTab(1);
        },

        toggleColumn(key) {
            const state = this.currentState();
            state.visibleColumns[key] = !state.visibleColumns[key];
        },

        visibleColumnCount() {
            const state = this.currentState();
            return Object.values(state.visibleColumns).filter(Boolean).length + 1;
        },

        overviewCards() {
            const summary = this.overview.summary || {};
            return [
                { label: 'Total Activity', value: this.formatNumber(summary.total_activity || 0), note: 'All captured work' },
                { label: 'Orders', value: this.formatNumber(summary.orders || 0), note: 'Orders created or touched' },
                { label: 'Packages', value: this.formatNumber(summary.packages || 0), note: 'Incoming and warehouse work' },
                { label: 'Security', value: this.formatNumber(summary.security || 0), note: summary.last_login_at ? `Last login ${this.formatDate(summary.last_login_at)}` : 'No login yet' },
            ];
        },

        initDateRange() {
            if (this.activeTab === 'overview' || !this.$refs.dateRangeInput) return;

            const setupPicker = () => {
                if (!window.$ || !window.moment || !window.$.fn.daterangepicker || !this.$refs.dateRangeInput) return;

                if (this.dateRangePicker) {
                    window.$(this.$refs.dateRangeInput).off('.daterangepicker');
                    window.$(this.$refs.dateRangeInput).data('daterangepicker')?.remove();
                }

                const state = this.currentState();
                const $input = window.$(this.$refs.dateRangeInput);
                $input.val(state.dateFrom && state.dateTo ? `${state.dateFrom} - ${state.dateTo}` : '');
                $input.daterangepicker({
                    autoUpdateInput: false,
                    alwaysShowCalendars: true,
                    opens: 'left',
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: 'Clear',
                    },
                    ranges: {
                        Today: [window.moment(), window.moment()],
                        Yesterday: [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                        'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                        'Last 30 Days': [window.moment().subtract(29, 'days'), window.moment()],
                        'This Month': [window.moment().startOf('month'), window.moment().endOf('month')],
                        'Last Month': [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                    },
                });

                $input.on('apply.daterangepicker', (event, picker) => {
                    state.dateFrom = picker.startDate.format('YYYY-MM-DD');
                    state.dateTo = picker.endDate.format('YYYY-MM-DD');
                    $input.val(`${state.dateFrom} - ${state.dateTo}`);
                    this.loadCurrentTab(1);
                });

                $input.on('cancel.daterangepicker', () => {
                    state.dateFrom = '';
                    state.dateTo = '';
                    $input.val('');
                    this.loadCurrentTab(1);
                });

                this.dateRangePicker = $input.data('daterangepicker');
            };

            if (window.$ && window.moment && window.$.fn.daterangepicker) {
                setupPicker();
                return;
            }

            ensureUserDetailDateRangeDependencies()
                .then(setupPicker)
                .catch((error) => {
                    console.error('Failed to initialize date range picker:', error);
                });
        },

        exportCurrent(format = 'csv') {
            const state = this.currentState();
            const rows = state.rows || [];
            if (!rows.length) {
                notify('There are no rows to export.', 'error');
                return;
            }

            if (format !== 'csv') return;

            const headers = ['Date', 'Module', 'Reference', 'Action', 'Status', 'Warehouse', 'Details'];
            const body = rows.map((row) => [
                this.formatDate(row.date),
                row.module,
                row.reference,
                row.action,
                row.status,
                row.warehouse,
                row.details,
            ]);
            const csv = [headers, ...body]
                .map((line) => line.map((value) => `"${String(value || '').replaceAll('"', '""')}"`).join(','))
                .join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${this.activeTab}-activity.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        },

        openEditModal() {
            this.modalMode = 'edit';
            this.editingUser = this.user;
            this.changePassword = false;
            this.formData = {
                name: this.user.name || '',
                email: this.user.email || '',
                phone: this.user.phone_input || this.user.phone || '',
                photo: null,
                photo_preview_url: this.user.photo_url || '',
                warehouse_id: this.user.warehouse?.id ? String(this.user.warehouse.id) : '',
                role_id: (this.user.roles && this.user.roles.length) ? String(this.user.roles[0].id) : '',
                is_active: this.user.is_active ? '1' : '0',
                password: '',
                password_confirmation: '',
            };
            this.formErrors = {};
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.formErrors = {};
        },

        handleUserPhoto(event) {
            const file = event.target.files?.[0] || null;
            this.formData.photo = file;
            if (this.formData.photo_preview_url?.startsWith('blob:')) {
                URL.revokeObjectURL(this.formData.photo_preview_url);
            }
            this.formData.photo_preview_url = file ? URL.createObjectURL(file) : (this.user.photo_url || '');
        },

        clearSelectedPhoto() {
            this.formData.photo = null;
            if (this.formData.photo_preview_url?.startsWith('blob:')) {
                URL.revokeObjectURL(this.formData.photo_preview_url);
            }
            this.formData.photo_preview_url = this.user.photo_url || '';
            if (this.$refs.userPhotoInput) {
                this.$refs.userPhotoInput.value = '';
            }
        },

        normalizePhoneInput() {
            this.formData.phone = String(this.formData.phone || '').replace(/\D/g, '').slice(0, 10);
            this.validatePhoneInput(this.formData.phone.length === 10);
        },

        validatePhoneInput(showIncomplete = true) {
            const phone = String(this.formData.phone || '');
            const validPrefixes = ['020', '024', '025', '026', '027', '050', '054', '055', '056', '057', '059'];

            if (!phone) {
                this.formErrors.phone = 'Phone number is required.';
                return false;
            }

            if (phone.length !== 10) {
                if (showIncomplete) {
                    this.formErrors.phone = 'Phone number must be exactly 10 digits.';
                }
                return false;
            }

            if (!phone.startsWith('0') || !validPrefixes.includes(phone.slice(0, 3))) {
                this.formErrors.phone = 'Please enter a valid Ghana phone number.';
                return false;
            }

            delete this.formErrors.phone;
            return true;
        },

        async submitForm() {
            this.submitting = true;
            this.formErrors = {};

            try {
                if (!this.validatePhoneInput(true)) return;

                const body = new FormData();
                body.append('_method', 'PUT');
                body.append('name', this.formData.name || '');
                body.append('email', this.formData.email || '');
                body.append('phone', this.formData.phone || '');
                body.append('role_id', this.formData.role_id || '');
                body.append('warehouse_id', this.formData.warehouse_id || '');
                if (!this.user.is_self) {
                    body.append('is_active', this.formData.is_active);
                }
                if (this.changePassword) {
                    body.append('password', this.formData.password || '');
                    body.append('password_confirmation', this.formData.password_confirmation || '');
                }
                if (this.formData.photo) {
                    body.append('profile_photo', this.formData.photo);
                }

                const response = await fetch(this.config.updateEndpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    if (response.status === 422 && result.errors) {
                        Object.entries(result.errors).forEach(([key, messages]) => {
                            this.formErrors[key] = messages[0];
                        });
                    } else {
                        this.formErrors.general = result.message || 'Unable to update user.';
                    }
                    return;
                }

                notify(result.message || 'User updated successfully.', 'success');
                window.location.reload();
            } catch (error) {
                console.error(error);
                this.formErrors.general = 'Unable to update user.';
                notify(this.formErrors.general, 'error');
            } finally {
                this.submitting = false;
            }
        },

        openStatusModal() {
            this.showStatusModal = true;
        },

        closeStatusModal() {
            if (this.statusSubmitting) return;
            this.showStatusModal = false;
        },

        async submitStatusToggle() {
            this.statusSubmitting = true;
            try {
                const response = await fetch(this.config.toggleActiveEndpoint, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.message || 'Unable to update user status.');
                notify(result.message || 'User status updated.', 'success');
                window.location.reload();
            } catch (error) {
                notify(error.message || 'Unable to update user status.', 'error');
            } finally {
                this.statusSubmitting = false;
            }
        },

        openImpersonationModal() {
            this.showImpersonationModal = true;
        },

        closeImpersonationModal() {
            if (this.impersonationSubmitting) return;
            this.showImpersonationModal = false;
        },

        async startImpersonation() {
            this.impersonationSubmitting = true;
            try {
                const response = await fetch(this.config.impersonateEndpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.message || 'Failed to login as this user.');
                window.location.href = result.redirect_url || '/admin/operations';
            } catch (error) {
                notify(error.message || 'Failed to login as this user.', 'error');
            } finally {
                this.impersonationSubmitting = false;
            }
        },

        formatDate(value) {
            if (!value) return '-';
            const date = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });
        },

        formatNumber(value) {
            return Number(value || 0).toLocaleString();
        },

        statusClass(status) {
            const value = String(status || '').toLowerCase();
            if (['active', 'completed', 'confirmed', 'successful', 'sent', 'paid', 'delivered', 'scanned'].some((needle) => value.includes(needle))) {
                return 'border-emerald-200 bg-emerald-50 text-emerald-700';
            }
            if (['failed', 'inactive', 'cancelled', 'rejected', 'error'].some((needle) => value.includes(needle))) {
                return 'border-rose-200 bg-rose-50 text-rose-700';
            }
            if (['pending', 'assigned', 'progress', 'review'].some((needle) => value.includes(needle))) {
                return 'border-amber-200 bg-amber-50 text-amber-700';
            }
            return 'border-slate-200 bg-slate-50 text-slate-600';
        },
    }));
});
