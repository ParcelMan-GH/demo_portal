import { apiRequest, clearToken, getToken } from './auth-client';

const PICKUP_STATUS_OPTIONS = [
    { value: 'assigned', label: 'Assigned' },
    { value: 'en_route', label: 'En Route' },
    { value: 'arrived', label: 'Arrived' },
    { value: 'picking_up', label: 'Picking Up' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const PICKUP_SORT_FIELDS = [
    { value: 'created_at', label: 'Created At' },
    { value: 'updated_at', label: 'Updated At' },
    { value: 'shipment_id', label: 'Shipment ID' },
    { value: 'status', label: 'Status' },
    { value: 'assigned_at', label: 'Assigned At' },
    { value: 'en_route_at', label: 'En Route At' },
    { value: 'arrived_at', label: 'Arrived At' },
    { value: 'picked_up_at', label: 'Picked Up At' },
    { value: 'completed_at', label: 'Completed At' },
    { value: 'cancelled_at', label: 'Cancelled At' },
];

function flattenErrors(payload) {
    const errors = payload?.errors;
    if (!errors || typeof errors !== 'object') {
        return [];
    }

    return Object.values(errors)
        .flatMap((value) => (Array.isArray(value) ? value : [value]))
        .filter((value) => typeof value === 'string' && value.trim() !== '');
}

function statusLabel(status) {
    if (!status) {
        return '-';
    }

    return String(status)
        .split('_')
        .map((token) => token.charAt(0).toUpperCase() + token.slice(1))
        .join(' ');
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function ensureDriverSessionOrRedirect() {
    const token = getToken('driver');
    if (!token) {
        window.location.href = '/driver/login';
        return false;
    }

    const profile = await apiRequest('/api/v1/driver/profile', { role: 'driver' });
    if (!profile.success) {
        clearToken('driver');
        window.location.href = '/driver/login';
        return false;
    }

    return true;
}

function driverPickupsListPage() {
    return {
        loading: true,
        alert: null,
        validationErrors: [],
        pickups: [],
        pickupStatuses: PICKUP_STATUS_OPTIONS,
        sortFields: PICKUP_SORT_FIELDS,
        shipmentOptions: [],
        filters: {
            shipment_id: '',
            search: '',
            status: [],
            from_date: '',
            to_date: '',
            limit: 15,
            offset: 0,
            sort_by: 'created_at',
            sort_order: 'desc',
        },
        pagination: {
            offset: 0,
            limit: 15,
            total: 0,
            has_more: false,
            next_offset: null,
            current_page: 1,
            last_page: 1,
            per_page: 15,
        },

        async init() {
            if (!(await ensureDriverSessionOrRedirect())) {
                return;
            }

            await this.loadShipmentOptions();
            await this.loadPickups();
        },

        statusLabel,
        formatDateTime,

        showAlert(type, message) {
            this.alert = { type, message };
        },

        setValidationErrors(payload) {
            this.validationErrors = flattenErrors(payload);
        },

        clearValidationErrors() {
            this.validationErrors = [];
        },

        buildQueryString() {
            const params = new URLSearchParams();
            if (this.filters.shipment_id) {
                params.append('shipment_id', String(this.filters.shipment_id));
            }
            if (this.filters.search.trim()) {
                params.append('search', this.filters.search.trim());
            }
            if (this.filters.from_date) {
                params.append('from_date', this.filters.from_date);
            }
            if (this.filters.to_date) {
                params.append('to_date', this.filters.to_date);
            }
            (this.filters.status || []).forEach((value) => {
                if (value) {
                    params.append('status[]', value);
                }
            });

            params.append('limit', String(this.filters.limit));
            params.append('offset', String(this.filters.offset));
            params.append('sort_by', this.filters.sort_by);
            params.append('sort_order', this.filters.sort_order);
            return params.toString();
        },

        async loadShipmentOptions() {
            const result = await apiRequest('/api/v1/driver/pickups?limit=100&offset=0&sort_by=created_at&sort_order=desc', {
                role: 'driver',
            });

            if (result.unauthorized) {
                clearToken('driver');
                window.location.href = '/driver/login';
                return;
            }

            if (!result.success) {
                this.shipmentOptions = [];
                return;
            }

            const pickups = Array.isArray(result.payload?.data?.pickups)
                ? result.payload.data.pickups
                : [];

            const map = new Map();
            pickups.forEach((pickup) => {
                if (pickup.shipment_id && pickup.shipment_number) {
                    map.set(String(pickup.shipment_id), {
                        id: pickup.shipment_id,
                        shipment_number: pickup.shipment_number,
                    });
                }
            });

            this.shipmentOptions = Array.from(map.values());
        },

        async loadPickups() {
            this.loading = true;
            this.alert = null;
            this.clearValidationErrors();

            const result = await apiRequest(`/api/v1/driver/pickups?${this.buildQueryString()}`, {
                role: 'driver',
            });

            if (result.unauthorized) {
                clearToken('driver');
                window.location.href = '/driver/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                this.loading = false;
                return;
            }

            const data = result.payload?.data || {};
            this.pickups = Array.isArray(data.pickups) ? data.pickups : [];
            this.pagination = data.pagination || this.pagination;
            this.filters.offset = Number(this.pagination.offset || 0);
            this.filters.limit = Number(this.pagination.limit || this.filters.limit);
            this.loading = false;
        },

        async applyFilters() {
            this.filters.offset = 0;
            await this.loadPickups();
        },

        async resetFilters() {
            this.filters.shipment_id = '';
            this.filters.search = '';
            this.filters.status = [];
            this.filters.from_date = '';
            this.filters.to_date = '';
            this.filters.limit = 15;
            this.filters.offset = 0;
            this.filters.sort_by = 'created_at';
            this.filters.sort_order = 'desc';
            await this.loadPickups();
        },

        async nextPage() {
            if (!this.pagination.has_more || this.pagination.next_offset === null) {
                return;
            }

            this.filters.offset = Number(this.pagination.next_offset);
            await this.loadPickups();
        },

        async previousPage() {
            if (this.filters.offset <= 0) {
                return;
            }

            this.filters.offset = Math.max(0, this.filters.offset - this.filters.limit);
            await this.loadPickups();
        },

        async startEnRoute(pickup) {
            if (!pickup?.id || pickup.status !== 'assigned') {
                return;
            }

            const proceed = window.confirm(`Start en route for pickup ${pickup.shipment_number}?`);
            if (!proceed) {
                return;
            }

            const result = await apiRequest(`/api/v1/driver/pickups/${pickup.id}/en-route`, {
                method: 'POST',
                role: 'driver',
            });

            if (result.unauthorized) {
                clearToken('driver');
                window.location.href = '/driver/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadPickups();
        },
    };
}

function driverPickupShowPage() {
    return {
        loading: true,
        pickupId: null,
        pickup: null,
        alert: null,
        validationErrors: [],
        toasts: [],
        nextToastId: 1,
        actionLoading: false,
        itemActionLoading: {},
        itemForms: {},
        arriveForm: {
            latitude: '',
            longitude: '',
        },
        finalizeForm: {
            latitude: '',
            longitude: '',
            notes: '',
        },

        async init() {
            this.pickupId = this.$el.dataset.pickupId;
            if (!(await ensureDriverSessionOrRedirect())) {
                return;
            }

            await this.loadPickup();
            this.loading = false;
        },

        statusLabel,
        formatDateTime,

        get canStartEnRoute() {
            return this.pickup?.status === 'assigned';
        },

        get canArrive() {
            return this.pickup?.status === 'en_route';
        },

        get canConfirmItems() {
            return ['arrived', 'picking_up'].includes(this.pickup?.status || '');
        },

        get canFinalize() {
            return ['arrived', 'picking_up'].includes(this.pickup?.status || '');
        },

        showAlert(type, message) {
            this.alert = { type, message };
            this.pushToast(type, message);
        },

        setValidationErrors(payload) {
            this.validationErrors = flattenErrors(payload);
            if (this.validationErrors.length > 0) {
                this.pushToast('error', this.validationErrors[0]);
            }
        },

        clearValidationErrors() {
            this.validationErrors = [];
        },

        pushToast(type, message, duration = 4500) {
            if (!message) {
                return;
            }

            const id = this.nextToastId++;
            this.toasts.push({ id, type, message });
            window.setTimeout(() => this.dismissToast(id), duration);
        },

        dismissToast(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },

        bootstrapItemForms() {
            const forms = {};
            const loading = {};
            const items = this.pickup?.shipment?.items || [];
            items.forEach((item) => {
                forms[item.id] = {
                    confirmed_quantity: item.pickup_confirmation?.confirmed_quantity ?? item.quantity ?? 0,
                    notes: item.pickup_confirmation?.notes || '',
                    photos: [],
                    remove_photo_ids: [],
                };
                loading[item.id] = false;
            });
            this.itemForms = forms;
            this.itemActionLoading = loading;
        },

        isItemActionLoading(itemId) {
            return this.itemActionLoading[itemId] === true;
        },

        async loadPickup() {
            this.alert = null;
            this.clearValidationErrors();

            const result = await apiRequest(`/api/v1/driver/pickups/${this.pickupId}`, {
                role: 'driver',
            });

            if (result.unauthorized) {
                clearToken('driver');
                window.location.href = '/driver/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                return;
            }

            this.pickup = result.payload?.data?.pickup || null;
            this.bootstrapItemForms();
        },

        onItemPhotosSelected(itemId, event) {
            const files = Array.from(event.target.files || []);
            if (!this.itemForms[itemId]) {
                return;
            }
            this.itemForms[itemId].photos = files;
        },

        async startEnRoute() {
            if (!this.pickup?.id || !this.canStartEnRoute) {
                return;
            }

            this.actionLoading = true;
            let result = null;
            try {
                result = await apiRequest(`/api/v1/driver/pickups/${this.pickup.id}/en-route`, {
                    method: 'POST',
                    role: 'driver',
                });
            } catch {
                this.showAlert('error', 'Unable to start en route right now.');
                return;
            } finally {
                this.actionLoading = false;
            }

            if (result.unauthorized) {
                clearToken('driver');
                window.location.href = '/driver/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadPickup();
        },

        async arriveAtPickup() {
            if (!this.pickup?.id || !this.canArrive) {
                return;
            }

            this.actionLoading = true;
            let result = null;
            try {
                result = await apiRequest(`/api/v1/driver/pickups/${this.pickup.id}/arrive`, {
                    method: 'POST',
                    role: 'driver',
                    data: {
                        latitude: this.arriveForm.latitude,
                        longitude: this.arriveForm.longitude,
                    },
                });
            } catch {
                this.showAlert('error', 'Unable to mark arrival right now.');
                return;
            } finally {
                this.actionLoading = false;
            }

            if (result.unauthorized) {
                clearToken('driver');
                window.location.href = '/driver/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadPickup();
        },

        async confirmItem(item) {
            if (!this.pickup?.id || !item?.id || !this.canConfirmItems) {
                return;
            }

            const form = this.itemForms[item.id];
            if (!form) {
                return;
            }

            const confirmedQuantity = Number(form.confirmed_quantity);
            if (Number.isNaN(confirmedQuantity) || confirmedQuantity < 0) {
                this.showAlert('error', 'Confirmed quantity must be zero or greater.');
                return;
            }

            const existingPhotos = Array.isArray(item.pickup_confirmation?.photos)
                ? item.pickup_confirmation.photos
                : [];
            const selectedRemoveIds = new Set((form.remove_photo_ids || []).map((id) => Number(id)));
            const remainingExistingCount = existingPhotos.filter(
                (photo) => !selectedRemoveIds.has(Number(photo.id))
            ).length;
            const newUploadCount = Array.isArray(form.photos) ? form.photos.length : 0;
            if ((remainingExistingCount + newUploadCount) < 1) {
                this.showAlert('error', 'At least one pickup photo is required. Upload a photo before saving.');
                return;
            }

            const formData = new FormData();
            formData.append('confirmed_quantity', String(confirmedQuantity));
            if (String(form.notes || '').trim() !== '') {
                formData.append('notes', String(form.notes).trim());
            }

            (form.remove_photo_ids || []).forEach((photoId) => {
                if (photoId) {
                    formData.append('remove_photo_ids[]', String(photoId));
                }
            });

            (form.photos || []).forEach((file) => {
                formData.append('photos[]', file);
            });

            this.itemActionLoading[item.id] = true;
            let result = null;
            try {
                result = await apiRequest(
                    `/api/v1/driver/pickups/${this.pickup.id}/items/${item.id}/confirm`,
                    {
                        method: 'POST',
                        role: 'driver',
                        data: formData,
                    }
                );
            } catch {
                this.showAlert('error', 'Unable to save item confirmation right now.');
                return;
            } finally {
                this.itemActionLoading[item.id] = false;
            }

            if (result.unauthorized) {
                clearToken('driver');
                window.location.href = '/driver/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadPickup();
        },

        async finalizePickup() {
            if (!this.pickup?.id || !this.canFinalize) {
                return;
            }

            const latitude = String(this.finalizeForm.latitude || '').trim();
            const longitude = String(this.finalizeForm.longitude || '').trim();
            if ((latitude && !longitude) || (!latitude && longitude)) {
                this.showAlert('error', 'Latitude and longitude must be provided together.');
                return;
            }

            this.actionLoading = true;
            let result = null;
            try {
                result = await apiRequest(`/api/v1/driver/pickups/${this.pickup.id}/confirm-pickup`, {
                    method: 'POST',
                    role: 'driver',
                    data: {
                        latitude: latitude || null,
                        longitude: longitude || null,
                        notes: String(this.finalizeForm.notes || '').trim() || null,
                    },
                });
            } catch {
                this.showAlert('error', 'Unable to finalize pickup right now.');
                return;
            } finally {
                this.actionLoading = false;
            }

            if (result.unauthorized) {
                clearToken('driver');
                window.location.href = '/driver/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadPickup();
        },
    };
}

function registerDriverPickupComponents() {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.data('driverPickupsListPage', driverPickupsListPage);
    window.Alpine.data('driverPickupShowPage', driverPickupShowPage);
}

if (window.Alpine) {
    registerDriverPickupComponents();
} else {
    document.addEventListener('alpine:init', registerDriverPickupComponents);
}
