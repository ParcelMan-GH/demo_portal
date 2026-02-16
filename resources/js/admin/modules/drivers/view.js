/**
 * Admin driver show page Alpine component
 * Extracted from Blade inline scripts and bundled via Vite.
 */

function driverShow() {
    return {
        config: {},
        driver: {},
        canManage: false,

        activeTab: 'assignments',
        showToggleModal: false,

        // Assignments state
        assignments: {
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
            is_active: true
        },

        init() {
            this.config = window.driverShowConfig;
            this.driver = this.config.driver;
            this.canManage = this.config.canManage;
            this.loadAssignments();
        },

        async loadAssignments() {
            this.assignments.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.assignments.page,
                    per_page: 10,
                    search: this.assignments.search,
                    status: this.assignments.status
                });

                const response = await fetch(`${this.config.assignmentsEndpoint}?${params}`);
                const data = await response.json();

                this.assignments.data = data.data;
                this.assignments.meta = data.meta;
            } catch (error) {
                console.error('Failed to load assignments:', error);
            } finally {
                this.assignments.loading = false;
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

        openEditModal() {
            this.form = {
                name: this.driver.name,
                email: this.driver.email,
                phone: this.driver.phone,
                password: '',
                vehicle_type: this.driver.vehicle_type || '',
                vehicle_number: this.driver.vehicle_number || '',
                license_number: this.driver.license_number || '',
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

                // Update local driver data
                this.driver.name = this.form.name;
                this.driver.email = this.form.email;
                this.driver.vehicle_type = this.form.vehicle_type;
                this.driver.vehicle_number = this.form.vehicle_number;
                this.driver.license_number = this.form.license_number;
                this.driver.is_active = this.form.is_active;

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
