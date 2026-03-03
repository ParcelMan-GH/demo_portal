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
        activeTab: "all",
        tabCounts: { all: 0, assigned: 0, en_route: 0, arrived: 0, picking_up: 0, completed: 0, cancelled: 0 },

        async init() {
            if (!(await ensureDriverSessionOrRedirect())) {
                return;
            }

            await Promise.all([this.loadShipmentOptions(), this.loadPickups(), this.loadTabCounts()]);
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

        async loadTabCounts() {
            const statuses = ['assigned', 'en_route', 'arrived', 'picking_up', 'completed', 'cancelled'];
            const calls = statuses.map((s) =>
                apiRequest(`/api/v1/driver/pickups?limit=1&offset=0&status[]=${s}`, { role: 'driver' })
            );
            const allResult = apiRequest('/api/v1/driver/pickups?limit=1&offset=0', { role: 'driver' });
            const [all, ...perStatus] = await Promise.all([allResult, ...calls]);
            if (all.success) this.tabCounts.all = all.payload?.data?.pagination?.total || 0;
            statuses.forEach((s, i) => {
                if (perStatus[i]?.success) this.tabCounts[s] = perStatus[i].payload?.data?.pagination?.total || 0;
            });
        },

        async switchTab(key) {
            const statusMap = {
                all: [],
                assigned: ['assigned'],
                en_route: ['en_route'],
                arrived: ['arrived'],
                picking_up: ['picking_up'],
                completed: ['completed'],
                cancelled: ['cancelled'],
            };
            if (!(key in statusMap)) return;
            this.activeTab = key;
            this.filters.status = [...statusMap[key]];
            this.filters.offset = 0;
            await this.loadPickups();
        },

        statusColor(status) {
            const map = {
                assigned: 'blue',
                en_route: 'orange',
                arrived: 'amber',
                picking_up: 'purple',
                completed: 'green',
                cancelled: 'red',
            };
            return map[status] || 'gray';
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
        showFinalizeModal: false,
        finalizeForm: {
            notes: '',
        },
        _lgInstance: null,
        editingConfirmation: {},
        confirmItemModalOpen: false,
        confirmItemModalItem: null,

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

        toggleEditConfirmation(itemId) {
            this.editingConfirmation[itemId] = !this.editingConfirmation[itemId];
        },

        openConfirmItemModal(item) {
            this.confirmItemModalItem = item;
            // Ensure form exists for this item
            if (!this.itemForms[item.id]) {
                this.itemForms[item.id] = {
                    confirmed_quantity: item.quantity,
                    notes: '',
                    photos: [],
                    remove_photo_ids: [],
                };
            }
            this.confirmItemModalOpen = true;
        },

        closeConfirmItemModal() {
            this.confirmItemModalOpen = false;
            this.confirmItemModalItem = null;
        },

        openLightbox(url, name) {
            this.openLightboxAt([{ url, original_name: name || '' }], 0);
        },

        openLightboxAt(images, startIndex) {
            const els = (Array.isArray(images) ? images : []).map(img => ({
                src: img.url,
                thumb: img.url,
                subHtml: img.original_name
                    ? `<div class="lg-sub-html"><p>${img.original_name}</p></div>`
                    : '',
            }));
            if (!els.length) return;

            // Destroy any previous instance
            if (this._lgInstance) {
                try { this._lgInstance.destroy(); } catch {}
                this._lgInstance = null;
            }

            let container = document.getElementById('_lg_container');
            if (!container) {
                container = document.createElement('div');
                container.id = '_lg_container';
                document.body.appendChild(container);
            }

            this._lgInstance = window.lightGallery(container, {
                dynamic: true,
                dynamicEl: els,
                plugins: [window.lgZoom, window.lgThumbnail],
                thumbnail: true,
                animateThumb: true,
                showThumbByDefault: true,
                toggleThumb: false,
                thumbWidth: 100,
                thumbHeight: '65px',
                thumbMargin: 4,
                download: false,
                zoomFromOrigin: false,
                allowMediaOverlap: false,
                speed: 300,
                startAnimationDuration: 250,
                loop: false,
                mobileSettings: {
                    controls: true,
                    showCloseIcon: true,
                    download: false,
                },
            });

            this._lgInstance.openGallery(Math.max(0, Math.min(startIndex || 0, els.length - 1)));

            container.addEventListener('lgAfterClose', () => {
                if (this._lgInstance) {
                    try { this._lgInstance.destroy(); } catch {}
                    this._lgInstance = null;
                }
            }, { once: true });
        },

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
            if (!['arrived', 'picking_up'].includes(this.pickup?.status || '')) return false;
            const items = this.pickup?.shipment?.items || [];
            if (items.length === 0) return true;
            return items.every((item) => item.pickup_confirmation != null);
        },

        get pendingItemsCount() {
            const items = this.pickup?.shipment?.items || [];
            return items.filter((item) => item.pickup_confirmation == null).length;
        },

        statusColor(status) {
            const map = {
                assigned: 'blue',
                en_route: 'orange',
                arrived: 'amber',
                picking_up: 'purple',
                completed: 'green',
                cancelled: 'red',
            };
            return map[status] || 'gray';
        },

        get heroClass() {
            const s = this.pickup?.status || '';
            if (s === 'cancelled') return 'no-progress';
            if (s === 'completed') return 'sh-hero-arrived';
            if (['arrived', 'picking_up'].includes(s)) return 'sh-hero-loading';
            if (s === 'en_route') return 'sh-hero-in_transit';
            return '';
        },

        get heroMessage() {
            const s = this.pickup?.status || '';
            const msgs = {
                assigned:    { title: "Ready for Pickup", text: "Tap \"Start En Route\" to begin heading to the pickup location." },
                en_route:    { title: "En Route to Pickup", text: "You are on your way. Tap \"Mark Arrived\" when you reach the location." },
                arrived:     { title: "Arrived at Location", text: "Confirm each item's quantity below, then finalize the pickup when ready." },
                picking_up:  { title: "Picking Up Items", text: "Confirm each item and finalize the pickup when all items are checked." },
                completed:   { title: "Pickup Complete", text: "This pickup has been completed and items are on their way to the warehouse." },
                cancelled:   { title: "Pickup Cancelled", text: "This pickup assignment has been cancelled." },
            };
            return msgs[s] || { title: 'Pickup Assignment', text: 'View pickup details and take action.' };
        },

        get progressSteps() {
            const statuses = ['assigned', 'en_route', 'arrived', 'picking_up', 'completed'];
            const current = this.pickup?.status || '';
            const isCancelled = current === 'cancelled';
            const currentIdx = statuses.indexOf(current);
            return {
                isCancelled,
                currentIdx: Math.max(0, currentIdx),
                total: statuses.length,
                steps: statuses.map((s, i) => {
                    const timelineKeyMap = { assigned: 'assigned', en_route: 'en_route', arrived: 'arrived_pickup', picking_up: null, completed: 'completed' };
                    const tKey = timelineKeyMap[s];
                    return {
                        label: s === 'en_route' ? 'En Route' : (s === 'picking_up' ? 'Picking Up' : s.charAt(0).toUpperCase() + s.slice(1)),
                        state: isCancelled ? 'pending' : (i < currentIdx ? 'done' : (i === currentIdx ? 'active' : 'pending')),
                        at: tKey ? (this.pickup?.timeline?.[tKey]?.at || null) : null,
                    };
                }),
            };
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

            if (!navigator.geolocation) {
                this.showAlert('error', 'Location services are not supported by your browser.');
                return;
            }

            this.actionLoading = true;

            // Prompt browser for location — triggers permission dialog if not yet decided
            let latitude = null;
            let longitude = null;
            try {
                const pos = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 12000,
                        maximumAge: 0,
                    });
                });
                latitude = pos.coords.latitude;
                longitude = pos.coords.longitude;
            } catch (err) {
                this.actionLoading = false;
                if (err.code === 1) {
                    // Permission denied
                    this.showAlert('error', 'Location access was denied. Please enable location in your browser or device settings and try again.');
                } else if (err.code === 2) {
                    this.showAlert('error', 'Your location could not be determined. Please check your GPS or Wi-Fi and try again.');
                } else {
                    this.showAlert('error', 'Location request timed out. Please try again.');
                }
                return;
            }

            let result = null;
            try {
                result = await apiRequest(`/api/v1/driver/pickups/${this.pickup.id}/arrive`, {
                    method: 'POST',
                    role: 'driver',
                    data: { latitude, longitude },
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

            this.editingConfirmation[item.id] = false;
            this.confirmItemModalOpen = false;
            this.confirmItemModalItem = null;
            this.showAlert('success', result.message);
            await this.loadPickup();
        },

        async finalizePickup() {
            if (!this.pickup?.id || !this.canFinalize) {
                return;
            }

            if (!navigator.geolocation) {
                this.showAlert('error', 'Location services are not supported by your browser.');
                return;
            }

            this.showFinalizeModal = false;
            this.actionLoading = true;

            // Capture location automatically
            let latitude = null;
            let longitude = null;
            try {
                const pos = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 12000,
                        maximumAge: 0,
                    });
                });
                latitude = pos.coords.latitude;
                longitude = pos.coords.longitude;
            } catch (err) {
                this.actionLoading = false;
                if (err.code === 1) {
                    this.showAlert('error', 'Location access was denied. Please enable location in your browser or device settings and try again.');
                } else if (err.code === 2) {
                    this.showAlert('error', 'Your location could not be determined. Please check your GPS or Wi-Fi and try again.');
                } else {
                    this.showAlert('error', 'Location request timed out. Please try again.');
                }
                return;
            }

            let result = null;
            try {
                result = await apiRequest(`/api/v1/driver/pickups/${this.pickup.id}/confirm-pickup`, {
                    method: 'POST',
                    role: 'driver',
                    data: {
                        latitude,
                        longitude,
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
