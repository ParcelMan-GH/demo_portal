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

        // Shipments state
        shipments: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            search: '',
            status: '',
            page: 1
        },

        // Activity logs state
        activity: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            search: '',
            action: '',
            page: 1
        },

        // OTP logs state
        otp: {
            data: [],
            meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },
            loading: false,
            purpose: '',
            page: 1
        },

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
            is_active: true
        },

        init() {
            this.config = window.vendorShowConfig;
            this.vendor = this.config.vendor;
            this.canManage = this.config.canManage;
            this.statuses = this.config.statuses;
            this.loadShipments();
        },

        async loadShipments() {
            this.shipments.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.shipments.page,
                    per_page: 10,
                    search: this.shipments.search,
                    status: this.shipments.status
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

        async loadActivityLogs() {
            this.activity.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.activity.page,
                    per_page: 10,
                    search: this.activity.search,
                    action: this.activity.action
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
                    per_page: 10,
                    purpose: this.otp.purpose
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

        openEditModal() {
            this.form = {
                name: this.vendor.name,
                business_name: this.vendor.business_name || '',
                email: this.vendor.email,
                phone: this.vendor.phone,
                is_active: this.vendor.is_active
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
                this.vendor.is_active = this.form.is_active;

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
