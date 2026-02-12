import { apiRequest, clearToken, getToken } from './auth-client';

const SHIPMENT_STATUS_OPTIONS = [
    { value: 'draft', label: 'Draft' },
    { value: 'submitted', label: 'Submitted' },
    { value: 'invoice_sent', label: 'Invoice Sent' },
    { value: 'invoice_accepted', label: 'Invoice Accepted' },
    { value: 'pickup_assigned', label: 'Pickup Assigned' },
    { value: 'picked_up', label: 'Picked Up' },
    { value: 'at_warehouse', label: 'At Warehouse' },
    { value: 'sorted', label: 'Sorted' },
    { value: 'in_transit', label: 'In Transit' },
    { value: 'at_destination', label: 'At Destination' },
    { value: 'out_for_delivery', label: 'Out for Delivery' },
    { value: 'delivered', label: 'Delivered' },
    { value: 'cancelled', label: 'Cancelled' },
];

const SORT_FIELD_OPTIONS = [
    { value: 'created_at', label: 'Created At' },
    { value: 'updated_at', label: 'Updated At' },
    { value: 'submitted_at', label: 'Submitted At' },
    { value: 'shipment_number', label: 'Shipment Number' },
    { value: 'status', label: 'Status' },
    { value: 'destination_mode', label: 'Destination Mode' },
    { value: 'pickup_contact_name', label: 'Pickup Contact' },
];

const DESTINATION_MODE_OPTIONS = [
    { value: 'single', label: 'Single destination' },
    { value: 'per_item', label: 'Per-item destination' },
];

const LOCATION_METHOD_OPTIONS = [
    { value: 'dropdown', label: 'Region + district' },
    { value: 'coordinates', label: 'Coordinates' },
    { value: 'gh_post', label: 'Ghana Post address' },
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

function normalizePhone(value) {
    let cleaned = String(value || '').replace(/[^\d+]/g, '');
    if (cleaned.includes('+')) {
        cleaned = `${cleaned[0] === '+' ? '+' : ''}${cleaned.replace(/\+/g, '')}`;
    }
    return cleaned.slice(0, 13);
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

function modeLabel(mode) {
    if (mode === 'single') {
        return 'Single destination';
    }
    if (mode === 'per_item') {
        return 'Per-item destination';
    }
    return '-';
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

function formatMoney(value, currency = 'GHS') {
    const amount = Number(value);
    if (Number.isNaN(amount)) {
        return '-';
    }

    try {
        return new Intl.NumberFormat('en-GH', {
            style: 'currency',
            currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `${amount.toFixed(2)} ${currency}`;
    }
}

function locationMethodFromObject(location) {
    if (!location || typeof location !== 'object') {
        return 'dropdown';
    }

    if (location.type === 'coordinates') {
        return 'coordinates';
    }
    if (location.type === 'gh_post') {
        return 'gh_post';
    }
    if (location.type === 'dropdown') {
        return 'dropdown';
    }
    if (location.latitude && location.longitude) {
        return 'coordinates';
    }
    if (location.gh_post_address) {
        return 'gh_post';
    }

    return 'dropdown';
}

function emptyShipmentForm() {
    return {
        destination_mode: 'single',
        pickup_contact_name: '',
        pickup_contact_phone: '',
        pickup_contact_phone_confirm: '',
        pickup_location_method: 'dropdown',
        pickup_region_id: '',
        pickup_district_id: '',
        pickup_town: '',
        pickup_latitude: '',
        pickup_longitude: '',
        pickup_gh_post_address: '',
        pickup_landmark: '',
        pickup_instructions: '',
        delivery_recipient_name: '',
        delivery_recipient_phone: '',
        delivery_recipient_phone_confirm: '',
        delivery_location_method: 'dropdown',
        delivery_region_id: '',
        delivery_district_id: '',
        delivery_town: '',
        delivery_latitude: '',
        delivery_longitude: '',
        delivery_gh_post_address: '',
        delivery_landmark: '',
        delivery_instructions: '',
    };
}

function emptyItemForm() {
    return {
        description: '',
        quantity: 1,
        images: [],
        remove_image_ids: [],
        delivery_recipient_name: '',
        delivery_recipient_phone: '',
        delivery_recipient_phone_confirm: '',
        delivery_location_method: 'dropdown',
        delivery_region_id: '',
        delivery_district_id: '',
        delivery_town: '',
        delivery_latitude: '',
        delivery_longitude: '',
        delivery_gh_post_address: '',
        delivery_landmark: '',
        delivery_instructions: '',
    };
}

async function ensureVendorSessionOrRedirect() {
    const token = getToken('vendor');
    if (!token) {
        window.location.href = '/vendor/login';
        return false;
    }

    const profile = await apiRequest('/api/v1/vendor/profile', { role: 'vendor' });
    if (!profile.success) {
        clearToken('vendor');
        window.location.href = '/vendor/login';
        return false;
    }

    return true;
}

function appendIfFilled(formData, key, value) {
    if (value === null || value === undefined) {
        return;
    }

    if (typeof value === 'string') {
        const trimmed = value.trim();
        if (trimmed === '') {
            return;
        }
        formData.append(key, trimmed);
        return;
    }

    formData.append(key, value);
}

function getDistrictsFromRegions(regions, regionId) {
    const numericId = Number(regionId);
    if (!numericId) {
        return [];
    }

    const region = regions.find((entry) => Number(entry.id) === numericId);
    if (!region) {
        return [];
    }

    return Array.isArray(region.districts) ? region.districts : [];
}

function buildShipmentPayload(form, includeDelivery = true) {
    const payload = {
        destination_mode: form.destination_mode,
        pickup_contact_name: form.pickup_contact_name.trim(),
        pickup_contact_phone: normalizePhone(form.pickup_contact_phone),
        pickup_contact_phone_confirm: normalizePhone(form.pickup_contact_phone_confirm),
        pickup_town: form.pickup_town.trim() || null,
        pickup_landmark: form.pickup_landmark.trim() || null,
        pickup_instructions: form.pickup_instructions.trim() || null,
    };

    if (form.pickup_location_method === 'dropdown') {
        payload.pickup_region_id = form.pickup_region_id || null;
        payload.pickup_district_id = form.pickup_district_id || null;
    } else if (form.pickup_location_method === 'coordinates') {
        payload.pickup_latitude = form.pickup_latitude || null;
        payload.pickup_longitude = form.pickup_longitude || null;
    } else if (form.pickup_location_method === 'gh_post') {
        payload.pickup_gh_post_address = form.pickup_gh_post_address.trim() || null;
    }

    if (includeDelivery && form.destination_mode === 'single') {
        payload.delivery_recipient_name = form.delivery_recipient_name.trim() || null;
        payload.delivery_recipient_phone = normalizePhone(form.delivery_recipient_phone) || null;
        payload.delivery_recipient_phone_confirm = normalizePhone(form.delivery_recipient_phone_confirm) || null;
        payload.delivery_town = form.delivery_town.trim() || null;
        payload.delivery_landmark = form.delivery_landmark.trim() || null;
        payload.delivery_instructions = form.delivery_instructions.trim() || null;

        if (form.delivery_location_method === 'dropdown') {
            payload.delivery_region_id = form.delivery_region_id || null;
            payload.delivery_district_id = form.delivery_district_id || null;
        } else if (form.delivery_location_method === 'coordinates') {
            payload.delivery_latitude = form.delivery_latitude || null;
            payload.delivery_longitude = form.delivery_longitude || null;
        } else if (form.delivery_location_method === 'gh_post') {
            payload.delivery_gh_post_address = form.delivery_gh_post_address.trim() || null;
        }
    }

    return payload;
}

function applyPerItemDeliveryToForm(formData, form) {
    appendIfFilled(formData, 'delivery_recipient_name', form.delivery_recipient_name);
    appendIfFilled(formData, 'delivery_recipient_phone', normalizePhone(form.delivery_recipient_phone));
    appendIfFilled(formData, 'delivery_recipient_phone_confirm', normalizePhone(form.delivery_recipient_phone_confirm));
    appendIfFilled(formData, 'delivery_town', form.delivery_town);
    appendIfFilled(formData, 'delivery_landmark', form.delivery_landmark);
    appendIfFilled(formData, 'delivery_instructions', form.delivery_instructions);

    if (form.delivery_location_method === 'dropdown') {
        appendIfFilled(formData, 'delivery_region_id', form.delivery_region_id);
        appendIfFilled(formData, 'delivery_district_id', form.delivery_district_id);
    } else if (form.delivery_location_method === 'coordinates') {
        appendIfFilled(formData, 'delivery_latitude', form.delivery_latitude);
        appendIfFilled(formData, 'delivery_longitude', form.delivery_longitude);
    } else if (form.delivery_location_method === 'gh_post') {
        appendIfFilled(formData, 'delivery_gh_post_address', form.delivery_gh_post_address);
    }
}

function vendorShipmentsListPage() {
    return {
        loading: true,
        shipments: [],
        alert: null,
        statuses: SHIPMENT_STATUS_OPTIONS,
        sortFields: SORT_FIELD_OPTIONS,
        filters: {
            search: '',
            statuses: [],
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
            if (!(await ensureVendorSessionOrRedirect())) {
                return;
            }
            await this.loadShipments();
        },

        statusLabel,
        modeLabel,
        formatDateTime,
        formatMoney,

        showAlert(type, message) {
            this.alert = { type, message };
        },

        buildQueryString() {
            const params = new URLSearchParams();
            if (this.filters.search.trim()) {
                params.append('search', this.filters.search.trim());
            }
            if (this.filters.from_date) {
                params.append('from_date', this.filters.from_date);
            }
            if (this.filters.to_date) {
                params.append('to_date', this.filters.to_date);
            }
            this.filters.statuses.forEach((value) => {
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

        async loadShipments() {
            this.loading = true;
            this.alert = null;

            const query = this.buildQueryString();
            const result = await apiRequest(`/api/v1/vendor/shipments?${query}`, {
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                this.loading = false;
                return;
            }

            const data = result.payload?.data || {};
            this.shipments = Array.isArray(data.shipments) ? data.shipments : [];
            this.pagination = data.pagination || this.pagination;
            this.filters.offset = Number(this.pagination.offset || 0);
            this.filters.limit = Number(this.pagination.limit || this.filters.limit);
            this.loading = false;
        },

        async applyFilters() {
            this.filters.offset = 0;
            await this.loadShipments();
        },

        async resetFilters() {
            this.filters.search = '';
            this.filters.statuses = [];
            this.filters.from_date = '';
            this.filters.to_date = '';
            this.filters.limit = 15;
            this.filters.offset = 0;
            this.filters.sort_by = 'created_at';
            this.filters.sort_order = 'desc';
            await this.loadShipments();
        },

        async nextPage() {
            if (!this.pagination.has_more || this.pagination.next_offset === null) {
                return;
            }
            this.filters.offset = Number(this.pagination.next_offset);
            await this.loadShipments();
        },

        async previousPage() {
            if (this.filters.offset <= 0) {
                return;
            }
            this.filters.offset = Math.max(0, this.filters.offset - this.filters.limit);
            await this.loadShipments();
        },

        async submitShipment(shipment) {
            if (!shipment?.id) {
                return;
            }
            const proceed = window.confirm(`Submit shipment ${shipment.shipment_number} for invoicing?`);
            if (!proceed) {
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/shipments/${shipment.id}/submit`, {
                method: 'POST',
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadShipments();
        },

        async deleteShipment(shipment) {
            if (!shipment?.id) {
                return;
            }
            const proceed = window.confirm(`Delete shipment ${shipment.shipment_number}? This cannot be undone.`);
            if (!proceed) {
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/shipments/${shipment.id}`, {
                method: 'DELETE',
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadShipments();
        },
    };
}

function vendorShipmentFormPage() {
    return {
        loading: true,
        saving: false,
        mode: 'create',
        shipmentId: null,
        alert: null,
        validationErrors: [],
        destinationModeOptions: DESTINATION_MODE_OPTIONS,
        locationMethodOptions: LOCATION_METHOD_OPTIONS,
        regions: [],
        pickupDistricts: [],
        deliveryDistricts: [],
        form: emptyShipmentForm(),

        async init() {
            this.mode = this.$el.dataset.mode || 'create';
            this.shipmentId = this.$el.dataset.shipmentId || null;

            if (!(await ensureVendorSessionOrRedirect())) {
                return;
            }

            await this.loadRegions();
            await this.prefillFromProfile();

            if (this.mode === 'edit' && this.shipmentId) {
                await this.loadShipment();
            }

            this.loading = false;
        },

        get isEditMode() {
            return this.mode === 'edit';
        },

        onPickupPhoneInput(event) {
            this.form.pickup_contact_phone = normalizePhone(event.target.value);
        },

        onPickupPhoneConfirmInput(event) {
            this.form.pickup_contact_phone_confirm = normalizePhone(event.target.value);
        },

        onDeliveryPhoneInput(event) {
            this.form.delivery_recipient_phone = normalizePhone(event.target.value);
        },

        onDeliveryPhoneConfirmInput(event) {
            this.form.delivery_recipient_phone_confirm = normalizePhone(event.target.value);
        },

        showAlert(type, message) {
            this.alert = { type, message };
        },

        clearErrors() {
            this.validationErrors = [];
        },

        setValidationErrors(payload) {
            this.validationErrors = flattenErrors(payload);
        },

        async loadRegions() {
            const result = await apiRequest('/api/v1/vendor/regions', { role: 'vendor' });
            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return false;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return false;
            }

            this.regions = Array.isArray(result.payload?.data?.regions)
                ? result.payload.data.regions
                : [];
            return true;
        },

        async prefillFromProfile() {
            if (this.isEditMode) {
                return;
            }

            const result = await apiRequest('/api/v1/vendor/profile', { role: 'vendor' });
            if (!result.success) {
                return;
            }

            const user = result.payload?.data?.user;
            if (!user) {
                return;
            }

            if (!this.form.pickup_contact_name) {
                this.form.pickup_contact_name = user.name || '';
            }
            if (!this.form.pickup_contact_phone) {
                this.form.pickup_contact_phone = user.phone || '';
                this.form.pickup_contact_phone_confirm = user.phone || '';
            }
        },

        async loadShipment() {
            const result = await apiRequest(`/api/v1/vendor/shipments/${this.shipmentId}`, { role: 'vendor' });
            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            const shipment = result.payload?.data?.shipment;
            if (!shipment) {
                this.showAlert('error', 'Shipment data is missing.');
                return;
            }

            this.populateFromShipment(shipment);
        },

        populateFromShipment(shipment) {
            this.form.destination_mode = shipment.destination_mode || 'single';

            this.form.pickup_contact_name = shipment.pickup?.contact_name || '';
            this.form.pickup_contact_phone = shipment.pickup?.contact_phone || '';
            this.form.pickup_contact_phone_confirm = shipment.pickup?.contact_phone || '';
            this.form.pickup_town = shipment.pickup?.location?.town || '';
            this.form.pickup_landmark = shipment.pickup?.location?.landmark || '';
            this.form.pickup_instructions = shipment.pickup?.instructions || '';
            this.form.pickup_location_method = locationMethodFromObject(shipment.pickup?.location);
            this.form.pickup_region_id = shipment.pickup?.location?.region_id || '';
            this.form.pickup_district_id = shipment.pickup?.location?.district_id || '';
            this.form.pickup_latitude = shipment.pickup?.location?.latitude || '';
            this.form.pickup_longitude = shipment.pickup?.location?.longitude || '';
            this.form.pickup_gh_post_address = shipment.pickup?.location?.gh_post_address || '';
            this.pickupDistricts = getDistrictsFromRegions(this.regions, this.form.pickup_region_id);

            this.form.delivery_recipient_name = shipment.delivery?.recipient_name || '';
            this.form.delivery_recipient_phone = shipment.delivery?.recipient_phone || '';
            this.form.delivery_recipient_phone_confirm = shipment.delivery?.recipient_phone || '';
            this.form.delivery_town = shipment.delivery?.location?.town || '';
            this.form.delivery_landmark = shipment.delivery?.location?.landmark || '';
            this.form.delivery_instructions = shipment.delivery?.instructions || '';
            this.form.delivery_location_method = locationMethodFromObject(shipment.delivery?.location);
            this.form.delivery_region_id = shipment.delivery?.location?.region_id || '';
            this.form.delivery_district_id = shipment.delivery?.location?.district_id || '';
            this.form.delivery_latitude = shipment.delivery?.location?.latitude || '';
            this.form.delivery_longitude = shipment.delivery?.location?.longitude || '';
            this.form.delivery_gh_post_address = shipment.delivery?.location?.gh_post_address || '';
            this.deliveryDistricts = getDistrictsFromRegions(this.regions, this.form.delivery_region_id);
        },

        onPickupRegionChange() {
            this.pickupDistricts = getDistrictsFromRegions(this.regions, this.form.pickup_region_id);
            if (!this.pickupDistricts.some((district) => Number(district.id) === Number(this.form.pickup_district_id))) {
                this.form.pickup_district_id = '';
            }
        },

        onDeliveryRegionChange() {
            this.deliveryDistricts = getDistrictsFromRegions(this.regions, this.form.delivery_region_id);
            if (!this.deliveryDistricts.some((district) => Number(district.id) === Number(this.form.delivery_district_id))) {
                this.form.delivery_district_id = '';
            }
        },

        onDestinationModeChange() {
            if (this.form.destination_mode === 'per_item') {
                this.form.delivery_recipient_name = '';
                this.form.delivery_recipient_phone = '';
                this.form.delivery_recipient_phone_confirm = '';
                this.form.delivery_region_id = '';
                this.form.delivery_district_id = '';
                this.form.delivery_town = '';
                this.form.delivery_latitude = '';
                this.form.delivery_longitude = '';
                this.form.delivery_gh_post_address = '';
                this.form.delivery_landmark = '';
                this.form.delivery_instructions = '';
            }
        },

        async saveShipment() {
            this.saving = true;
            this.alert = null;
            this.clearErrors();

            const payload = buildShipmentPayload(this.form, true);
            const endpoint = this.isEditMode
                ? `/api/v1/vendor/shipments/${this.shipmentId}`
                : '/api/v1/vendor/shipments';
            const method = this.isEditMode ? 'PUT' : 'POST';

            const result = await apiRequest(endpoint, {
                method,
                role: 'vendor',
                data: payload,
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.setValidationErrors(result.payload);
                this.showAlert('error', result.message);
                this.saving = false;
                return;
            }

            const shipment = result.payload?.data?.shipment;
            this.showAlert('success', result.message);

            if (shipment?.id) {
                window.location.href = `/vendor/shipments/${shipment.id}`;
                return;
            }

            this.saving = false;
        },
    };
}

function vendorShipmentShowPage() {
    return {
        loading: true,
        shipmentId: null,
        shipment: null,
        alert: null,
        itemAlert: null,
        itemErrors: [],
        editItemErrors: [],
        addingItem: false,
        editingItemId: null,
        itemForm: emptyItemForm(),
        editItemForm: emptyItemForm(),
        destinationMode: 'single',
        regions: [],
        itemDistricts: [],
        editItemDistricts: [],
        imageUploadFiles: {},
        imageUploadState: {},

        async init() {
            this.shipmentId = this.$el.dataset.shipmentId;
            if (!(await ensureVendorSessionOrRedirect())) {
                return;
            }

            await this.loadRegions();
            await this.loadShipment();
            this.loading = false;
        },

        statusLabel,
        modeLabel,
        formatDateTime,

        get canEditShipment() {
            return Boolean(this.shipment?.can_edit);
        },

        get canDeleteShipment() {
            return Boolean(this.shipment?.can_delete);
        },

        get canSubmitShipment() {
            return Boolean(this.shipment?.can_submit);
        },

        get isPerItemDestination() {
            return this.destinationMode === 'per_item';
        },

        get currentInvoice() {
            return this.shipment?.invoice || null;
        },

        get invoiceHistory() {
            return Array.isArray(this.shipment?.invoice_history)
                ? this.shipment.invoice_history
                : [];
        },

        get canRespondToInvoice() {
            return this.currentInvoice?.status === 'sent';
        },

        get pickupAssignment() {
            return this.shipment?.pickup_assignment || null;
        },

        onItemPhoneInput(event) {
            this.itemForm.delivery_recipient_phone = normalizePhone(event.target.value);
        },

        onItemPhoneConfirmInput(event) {
            this.itemForm.delivery_recipient_phone_confirm = normalizePhone(event.target.value);
        },

        onEditItemPhoneInput(event) {
            this.editItemForm.delivery_recipient_phone = normalizePhone(event.target.value);
        },

        onEditItemPhoneConfirmInput(event) {
            this.editItemForm.delivery_recipient_phone_confirm = normalizePhone(event.target.value);
        },

        showAlert(type, message) {
            this.alert = { type, message };
        },

        showItemAlert(type, message) {
            this.itemAlert = { type, message };
        },

        setItemErrors(payload, target = 'item') {
            const errors = flattenErrors(payload);
            if (target === 'edit') {
                this.editItemErrors = errors;
            } else {
                this.itemErrors = errors;
            }
        },

        async loadRegions() {
            const result = await apiRequest('/api/v1/vendor/regions', { role: 'vendor' });
            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }
            if (!result.success) {
                return;
            }
            this.regions = Array.isArray(result.payload?.data?.regions)
                ? result.payload.data.regions
                : [];
        },

        async loadShipment() {
            const result = await apiRequest(`/api/v1/vendor/shipments/${this.shipmentId}`, {
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.shipment = result.payload?.data?.shipment || null;
            if (!Array.isArray(this.shipment?.items)) {
                this.shipment.items = [];
            }
            this.destinationMode = this.shipment?.destination_mode || 'single';
        },

        onItemRegionChange() {
            this.itemDistricts = getDistrictsFromRegions(this.regions, this.itemForm.delivery_region_id);
            if (!this.itemDistricts.some((district) => Number(district.id) === Number(this.itemForm.delivery_district_id))) {
                this.itemForm.delivery_district_id = '';
            }
        },

        onEditItemRegionChange() {
            this.editItemDistricts = getDistrictsFromRegions(this.regions, this.editItemForm.delivery_region_id);
            if (!this.editItemDistricts.some((district) => Number(district.id) === Number(this.editItemForm.delivery_district_id))) {
                this.editItemForm.delivery_district_id = '';
            }
        },

        onItemImagesSelected(event, formType = 'create') {
            const files = Array.from(event.target.files || []);
            if (formType === 'edit') {
                this.editItemForm.images = files;
            } else {
                this.itemForm.images = files;
            }
        },

        openAddItem() {
            this.addingItem = true;
            this.itemAlert = null;
            this.itemErrors = [];
            this.itemForm = emptyItemForm();
            this.itemDistricts = [];
        },

        cancelAddItem() {
            this.addingItem = false;
            this.itemForm = emptyItemForm();
            this.itemDistricts = [];
            this.itemErrors = [];
        },

        async addItem() {
            this.itemAlert = null;
            this.itemErrors = [];

            const formData = new FormData();
            appendIfFilled(formData, 'description', this.itemForm.description);
            appendIfFilled(formData, 'quantity', this.itemForm.quantity);

            if (this.isPerItemDestination) {
                applyPerItemDeliveryToForm(formData, this.itemForm);
            }

            (this.itemForm.images || []).forEach((file) => {
                formData.append('images[]', file);
            });

            const result = await apiRequest(`/api/v1/vendor/shipments/${this.shipmentId}/items`, {
                method: 'POST',
                role: 'vendor',
                data: formData,
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.setItemErrors(result.payload, 'item');
                this.showItemAlert('error', result.message);
                return;
            }

            this.showItemAlert('success', result.message);
            this.cancelAddItem();
            await this.loadShipment();
        },

        openEditItem(item) {
            this.editingItemId = item.id;
            this.editItemErrors = [];
            this.itemAlert = null;

            this.editItemForm = {
                ...emptyItemForm(),
                description: item.description || '',
                quantity: item.quantity || 1,
            };

            if (this.isPerItemDestination && item.delivery) {
                this.editItemForm.delivery_recipient_name = item.delivery.recipient_name || '';
                this.editItemForm.delivery_recipient_phone = item.delivery.recipient_phone || '';
                this.editItemForm.delivery_recipient_phone_confirm = item.delivery.recipient_phone || '';
                this.editItemForm.delivery_town = item.delivery.location?.town || '';
                this.editItemForm.delivery_landmark = item.delivery.location?.landmark || '';
                this.editItemForm.delivery_instructions = item.delivery.instructions || '';
                this.editItemForm.delivery_region_id = item.delivery.location?.region_id || '';
                this.editItemForm.delivery_district_id = item.delivery.location?.district_id || '';
                this.editItemForm.delivery_latitude = item.delivery.location?.latitude || '';
                this.editItemForm.delivery_longitude = item.delivery.location?.longitude || '';
                this.editItemForm.delivery_gh_post_address = item.delivery.location?.gh_post_address || '';
                this.editItemForm.delivery_location_method = locationMethodFromObject(item.delivery.location);
                this.editItemDistricts = getDistrictsFromRegions(this.regions, this.editItemForm.delivery_region_id);
            } else {
                this.editItemDistricts = [];
            }
        },

        cancelEditItem() {
            this.editingItemId = null;
            this.editItemForm = emptyItemForm();
            this.editItemDistricts = [];
            this.editItemErrors = [];
        },

        async updateItem(item) {
            this.itemAlert = null;
            this.editItemErrors = [];

            const formData = new FormData();
            formData.append('_method', 'PUT');
            appendIfFilled(formData, 'description', this.editItemForm.description);
            appendIfFilled(formData, 'quantity', this.editItemForm.quantity);

            if (this.isPerItemDestination) {
                applyPerItemDeliveryToForm(formData, this.editItemForm);
            }

            (this.editItemForm.remove_image_ids || []).forEach((imageId) => {
                appendIfFilled(formData, 'remove_image_ids[]', imageId);
            });

            (this.editItemForm.images || []).forEach((file) => {
                formData.append('images[]', file);
            });

            const result = await apiRequest(`/api/v1/vendor/shipments/${this.shipmentId}/items/${item.id}`, {
                method: 'POST',
                role: 'vendor',
                data: formData,
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.setItemErrors(result.payload, 'edit');
                this.showItemAlert('error', result.message);
                return;
            }

            this.showItemAlert('success', result.message);
            this.cancelEditItem();
            await this.loadShipment();
        },

        async deleteItem(item) {
            const proceed = window.confirm(`Delete item "${item.description}"?`);
            if (!proceed) {
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/shipments/${this.shipmentId}/items/${item.id}`, {
                method: 'DELETE',
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showItemAlert('error', result.message);
                return;
            }

            this.showItemAlert('success', result.message);
            await this.loadShipment();
        },

        setInlineUploadFiles(itemId, event) {
            this.imageUploadFiles[itemId] = Array.from(event.target.files || []);
        },

        async uploadItemImages(item) {
            const files = this.imageUploadFiles[item.id] || [];
            if (!files.length) {
                this.showItemAlert('error', 'Select one or more images first.');
                return;
            }

            this.imageUploadState[item.id] = true;
            const formData = new FormData();
            files.forEach((file) => formData.append('images[]', file));

            const result = await apiRequest(`/api/v1/vendor/shipments/${this.shipmentId}/items/${item.id}/images`, {
                method: 'POST',
                role: 'vendor',
                data: formData,
            });

            this.imageUploadState[item.id] = false;

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showItemAlert('error', result.message);
                return;
            }

            this.showItemAlert('success', result.message);
            this.imageUploadFiles[item.id] = [];
            await this.loadShipment();
        },

        async deleteItemImage(item, image) {
            const proceed = window.confirm(`Delete image "${image.original_name}"?`);
            if (!proceed) {
                return;
            }

            const result = await apiRequest(
                `/api/v1/vendor/shipments/${this.shipmentId}/items/${item.id}/images/${image.id}`,
                {
                    method: 'DELETE',
                    role: 'vendor',
                }
            );

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showItemAlert('error', result.message);
                return;
            }

            this.showItemAlert('success', result.message);
            await this.loadShipment();
        },

        async submitShipment() {
            const proceed = window.confirm(`Submit shipment ${this.shipment?.shipment_number} for invoicing?`);
            if (!proceed) {
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/shipments/${this.shipmentId}/submit`, {
                method: 'POST',
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadShipment();
        },

        async acceptCurrentInvoice() {
            const invoiceId = this.currentInvoice?.id;
            if (!invoiceId || !this.canRespondToInvoice) {
                return;
            }

            const vendorNotes = window.prompt('Vendor notes (optional):', '') ?? '';

            const result = await apiRequest(`/api/v1/vendor/invoices/${invoiceId}/accept`, {
                method: 'POST',
                role: 'vendor',
                data: {
                    vendor_notes: vendorNotes.trim() || null,
                },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadShipment();
        },

        async rejectCurrentInvoice() {
            const invoiceId = this.currentInvoice?.id;
            if (!invoiceId || !this.canRespondToInvoice) {
                return;
            }

            const rejectionReason = window.prompt('Rejection reason (required):', '');
            if (!rejectionReason || !rejectionReason.trim()) {
                this.showAlert('error', 'Rejection reason is required.');
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/invoices/${invoiceId}/reject`, {
                method: 'POST',
                role: 'vendor',
                data: {
                    rejection_reason: rejectionReason.trim(),
                },
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            this.showAlert('success', result.message);
            await this.loadShipment();
        },

        async deleteShipment() {
            const proceed = window.confirm(`Delete shipment ${this.shipment?.shipment_number}?`);
            if (!proceed) {
                return;
            }

            const result = await apiRequest(`/api/v1/vendor/shipments/${this.shipmentId}`, {
                method: 'DELETE',
                role: 'vendor',
            });

            if (result.unauthorized) {
                clearToken('vendor');
                window.location.href = '/vendor/login';
                return;
            }

            if (!result.success) {
                this.showAlert('error', result.message);
                return;
            }

            window.location.href = '/vendor/shipments';
        },
    };
}

function registerVendorShipmentComponents() {
    if (!window.Alpine) {
        return;
    }

    window.Alpine.data('vendorShipmentsListPage', vendorShipmentsListPage);
    window.Alpine.data('vendorShipmentFormPage', vendorShipmentFormPage);
    window.Alpine.data('vendorShipmentShowPage', vendorShipmentShowPage);
}

if (window.Alpine) {
    registerVendorShipmentComponents();
} else {
    document.addEventListener('alpine:init', registerVendorShipmentComponents);
}
