import { parseJsonAttribute } from '../../core/config.js';
import { createReceivingWorkspaceState } from '../../../shared/receiving-workspace.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-receipt-show-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-receipt-show-config', null);
    if (!config) {
        console.error('Invalid warehouse receipt show config JSON');
    }

    return config;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function endpointWithItem(urlTemplate, itemId) {
    return (urlTemplate || '').replace('__ITEM__', String(itemId));
}

function endpointWithId(urlTemplate, id) {
    return (urlTemplate || '').replace('__ID__', String(id));
}

function registerWarehouseReceiptShowPage() {
    if (!window.Alpine) return;

    const config = getConfig();
    if (!config || !config.finalize_url) return;

    window.Alpine.data('warehouseReceiptShowPage', () => ({
        ...createReceivingWorkspaceState(),
        activeTab: 'receiving',
        saving: false,
        showFinalizeModal: false,
        finalizeNotes: '',
        approvalReason: '',
        canReceive: config.can_receive !== false,
        canApproveDiscrepancy: Boolean(config.can_approve_discrepancy),
        shipment: config.shipment || {},
        assignment: config.assignment || null,
        receipt: config.receipt || null,
        items: Array.isArray(config.items) ? config.items : [],
        receiving: {
            canAutoGroup: Boolean(config.can_auto_group),
            autoGroupLockReason: config.auto_group_lock_reason || '',
            autoGrouping: false,
        },
        pendingFiles: {},
        removePhotoMap: {},
        addPackageModal: {
            open: false,
            saving: false,
            description: '',
            quantity: 1,
            delivery_method: 'direct',
            files: [],
        },
        receivingAddPackageModal: {
            open: false,
            saving: false,
            description: '',
            quantity: 1,
            delivery_recipient_name: '',
            delivery_recipient_phone: '',
            delivery_region_id: '',
            delivery_district_id: '',
            delivery_town: '',
            delivery_landmark: '',
            delivery_instructions: '',
            delivery_method: 'direct',
            forward_to_warehouse_id: '',
            _town_query: '',
            _town_results: [],
            _town_open: false,
            _town_loading: false,
            _town_request: 0,
            _town_debounce: null,
            _town_linked: false,
            _town_context: '',
            _town_selected_display: null,
            _receipt_photo_files: [],
        },
        splitModal: {
            open: false,
            saving: false,
            pkg: null,
            selectedPhotoIds: [],
        },
        sharedDestinationModal: {
            open: false,
            saving: false,
            form: null,
        },

        receiveModal: { open: false, itemId: null, itemIndex: -1 },
        photoModal: { open: false, title: '', photos: [], index: 0 },
        printLabelModal: {
            open: false,
            itemId: null,
            trackingCode: '',
            receivedQuantity: 1,
            labelCount: 1,
            printing: false,
        },

        init() {
            this.items = this.items.map((item) => this.prepareReceivingTownState(item));
            this.receivingAddPackageModal = this.receivingAddPackageDraft();
            if (!this.receipt && this.items.length > 0) {
                this.receipt = { status: 'draft' };
            }
        },

        isFinalized() {
            return (this.receipt?.status || '') === 'finalized';
        },

        receiptStatusLabel() {
            const status = this.receipt?.status || 'draft';
            switch (status) {
                case 'discrepancy_open':
                    return 'Discrepancy Open';
                case 'finalized':
                    return 'Finalized';
                default:
                    return 'Draft';
            }
        },

        isPerItemMode() {
            return (this.shipment?.destination_mode || 'single') === 'per_item';
        },

        pickupLocationSummary() {
            const town = this.shipment?.pickup_town;
            const district = this.shipment?.pickup_district?.name;
            const region = this.shipment?.pickup_region?.name;

            if (town || district || region) {
                return [town, district, region].filter(Boolean).join(', ');
            }

            if (this.shipment?.pickup_latitude && this.shipment?.pickup_longitude) {
                return `${this.shipment.pickup_latitude}, ${this.shipment.pickup_longitude}`;
            }

            if (this.shipment?.pickup_gh_post_address) {
                return this.shipment.pickup_gh_post_address;
            }

            return '-';
        },

        assignmentDriverName() {
            return this.assignment?.driver?.name || this.assignment?.driver_name || 'No rider assigned';
        },

        assignmentDriverPhone() {
            return this.assignment?.driver?.phone || this.assignment?.driver_phone || '-';
        },

        assignmentWarehouseName() {
            return this.assignment?.target_warehouse?.name || this.assignment?.targetWarehouse?.name || this.assignment?.target_warehouse_name || 'No target warehouse';
        },

        assignmentWarehouseCode() {
            return this.assignment?.target_warehouse?.code || this.assignment?.targetWarehouse?.code || this.assignment?.target_warehouse_code || '';
        },

        receivingSharedDestinationSummary() {
            const location = [
                this.shipment?.delivery_town,
                this.shipment?.delivery_district_name,
                this.shipment?.delivery_region_name,
            ].filter(Boolean).join(', ');

            return location || 'No shared destination set';
        },

        receivingTownContext(districtName, regionName) {
            return [districtName, regionName].filter(Boolean).join(', ');
        },

        receivingTownDisplay(town, districtName = '', regionName = '', isLinked = false) {
            if (!town) return '';
            const context = this.receivingTownContext(districtName, regionName);
            return isLinked && context ? `${town} - ${context}` : town;
        },

        prepareReceivingTownState(item) {
            const prepared = { ...(item || {}) };
            const isLinked = Boolean(prepared.delivery_town && prepared.delivery_region_id && prepared.delivery_district_id);
            prepared._town_query = this.receivingTownDisplay(
                prepared.delivery_town || '',
                prepared.delivery_district_name || '',
                prepared.delivery_region_name || '',
                isLinked
            );
            prepared._town_results = [];
            prepared._town_open = false;
            prepared._town_loading = false;
            prepared._town_debounce = null;
            prepared._town_request = 0;
            prepared._town_linked = isLinked;
            prepared._town_context = isLinked
                ? this.receivingTownContext(prepared.delivery_district_name || '', prepared.delivery_region_name || '')
                : '';
            prepared._town_selected_display = isLinked ? prepared._town_query : null;
            return prepared;
        },

        openSharedDestinationModal() {
            if (this.isPerItemMode()) return;

            this.sharedDestinationModal = {
                open: true,
                saving: false,
                form: {
                    delivery_recipient_name: this.shipment?.delivery_recipient_name || '',
                    delivery_recipient_phone: this.shipment?.delivery_recipient_phone || '',
                    delivery_region_id: this.shipment?.delivery_region_id || '',
                    delivery_district_id: this.shipment?.delivery_district_id || '',
                    delivery_town: this.shipment?.delivery_town || '',
                    delivery_landmark: this.shipment?.delivery_landmark || '',
                    delivery_instructions: this.shipment?.delivery_instructions || '',
                    delivery_method: (this.items || []).some((item) => item.delivery_method === 'bus_handoff') ? 'bus_handoff' : 'direct',
                },
            };
        },

        openReceivingSharedDestinationModal() {
            this.openSharedDestinationModal();
        },

        closeSharedDestinationModal() {
            if (this.sharedDestinationModal.saving) return;
            this.sharedDestinationModal = { open: false, saving: false, form: null };
        },

        async saveSharedDestination() {
            const form = this.sharedDestinationModal.form;
            if (!form || this.sharedDestinationModal.saving || !config.save_shared_destination_url) return;

            this.sharedDestinationModal.saving = true;
            try {
                const response = await fetch(config.save_shared_destination_url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        delivery_recipient_name: form.delivery_recipient_name || null,
                        delivery_recipient_phone: form.delivery_recipient_phone || null,
                        delivery_region_id: form.delivery_region_id || null,
                        delivery_district_id: form.delivery_district_id || null,
                        delivery_town: form.delivery_town || null,
                        delivery_landmark: form.delivery_landmark || null,
                        delivery_instructions: form.delivery_instructions || null,
                        delivery_method: form.delivery_method || 'direct',
                    }),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to save shared destination.');
                }

                if (result.data?.shipment) {
                    this.shipment = result.data.shipment;
                }
                if (Array.isArray(result.data?.items)) {
                    this.items = result.data.items;
                }

                this.sharedDestinationModal = { open: false, saving: false, form: null };
                window.showToast?.(result.message || 'Shared destination saved.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to save shared destination.', 'error');
                this.sharedDestinationModal.saving = false;
            }
        },

        hasDiscrepancies() {
            return this.items.some((item) => (item.discrepancy_type || 'none') !== 'none');
        },

        receivedItemCount() {
            return this.items.filter((item) => this.receivingPackageIsReceived(item)).length;
        },

        receivingDeclaredQuantity() {
            const firstDeclared = Number(this.items?.[0]?.vendor_declared_quantity ?? 0);
            if (Number.isFinite(firstDeclared) && firstDeclared > 0) {
                return firstDeclared;
            }

            return this.items.reduce((total, item) => {
                const quantity = Number(item?.vendor_quantity ?? 0);
                return total + (Number.isFinite(quantity) ? quantity : 0);
            }, 0);
        },

        deliveryMethodLabel(item) {
            return item?.delivery_method === 'bus_handoff' ? 'Bus handoff' : 'Direct delivery';
        },

        deliveryMethodClass(item) {
            return item?.delivery_method === 'bus_handoff' ? 'text-violet-700' : 'text-orange-700';
        },

        receivingDeliveryFeeLabel() {
            return 'No delivery fee';
        },

        receivingDeliveryFeeClass() {
            return 'text-slate-400';
        },

        packageDeliveryStatusLabel() {
            return '';
        },

        packageDeliveryStatusClass() {
            return 'text-slate-400';
        },

        packageCustodySummary(item) {
            return item?.barcode_value ? 'Label printed' : 'No labels';
        },

        packageCustodyClass(item) {
            return item?.barcode_value ? 'text-slate-600' : 'text-slate-400';
        },

        packageCustodyDetailLines(item) {
            if (!item?.barcode_value) return ['Receive and print'];
            return [`Prints: ${Number(item?.barcode_print_count || 0)}`];
        },

        packageCustodyCanOpen(item) {
            return Boolean(item?.barcode_value);
        },

        openPackageCustodyModal(item) {
            if (!this.packageCustodyCanOpen(item)) return;
            this.openItemPhotos(item);
        },

        vendorPhotoUrl(photo) {
            return typeof photo === 'string' ? photo : photo?.url;
        },

        itemPhotoList(item) {
            const vendorPhotos = (item?.vendor_photos || []).map((photo) => ({ url: this.vendorPhotoUrl(photo), label: 'Vendor' }));
            const driverPhotos = (item?.driver_photos || []).map((photo) => ({ url: photo?.url, label: 'Rider' }));
            const receiptPhotos = (item?.photos || []).map((photo) => ({ url: photo?.url, label: 'Receipt' }));

            return [...vendorPhotos, ...driverPhotos, ...receiptPhotos].filter((photo) => photo.url);
        },

        itemPhotoCount(item) {
            return this.itemPhotoList(item).length;
        },

        receivingPhotoCount() {
            return this.items.reduce((total, item) => total + this.itemPhotoCount(item), 0);
        },

        openItemPhotos(item) {
            const photos = this.itemPhotoList(item);
            if (photos.length === 0) {
                window.showToast?.('No photos available for this package.', 'info');
                return;
            }

            this.photoModal = {
                open: true,
                title: item?.description || item?.tracking_code || 'Package photos',
                photos,
                index: 0,
            };
        },

        openReceivingPhotosModal(item) {
            this.openItemPhotos(item);
        },

        closeItemPhotos() {
            this.photoModal = { open: false, title: '', photos: [], index: 0 };
        },

        nextItemPhoto() {
            if (!this.photoModal.photos.length) return;
            this.photoModal.index = (this.photoModal.index + 1) % this.photoModal.photos.length;
        },

        previousItemPhoto() {
            if (!this.photoModal.photos.length) return;
            this.photoModal.index = (this.photoModal.index - 1 + this.photoModal.photos.length) % this.photoModal.photos.length;
        },

        setItemFiles(itemId, event) {
            const files = Array.from(event?.target?.files || []);
            this.pendingFiles[itemId] = files;
        },

        isPhotoMarkedForRemoval(itemId, photoId) {
            const selected = this.removePhotoMap[itemId] || [];
            return selected.includes(Number(photoId));
        },

        toggleRemovePhoto(itemId, photoId, checked) {
            const normalizedItemId = Number(itemId);
            const normalizedPhotoId = Number(photoId);
            const current = Array.isArray(this.removePhotoMap[normalizedItemId])
                ? this.removePhotoMap[normalizedItemId]
                : [];

            if (checked) {
                this.removePhotoMap[normalizedItemId] = Array.from(new Set([...current, normalizedPhotoId]));
                return;
            }

            this.removePhotoMap[normalizedItemId] = current.filter((id) => Number(id) !== normalizedPhotoId);
        },

        applyReceivingPayload(data = {}) {
            if (data.shipment) {
                this.shipment = data.shipment;
            }
            if (Array.isArray(data.items)) {
                this.items = data.items.map((item) => this.prepareReceivingTownState(item));
            }
            if (Object.prototype.hasOwnProperty.call(data, 'receipt')) {
                this.receipt = data.receipt || this.receipt;
            }
            if (Object.prototype.hasOwnProperty.call(data, 'can_auto_group')) {
                this.receiving.canAutoGroup = Boolean(data.can_auto_group);
            }
            if (Object.prototype.hasOwnProperty.call(data, 'auto_group_lock_reason')) {
                this.receiving.autoGroupLockReason = data.auto_group_lock_reason || '';
            }
        },

        receivingAddPackageDraft(overrides = {}) {
            return {
                open: false,
                saving: false,
                description: '',
                quantity: 1,
                delivery_recipient_name: '',
                delivery_recipient_phone: '',
                delivery_region_id: '',
                delivery_district_id: '',
                delivery_town: '',
                delivery_landmark: '',
                delivery_instructions: '',
                delivery_method: 'direct',
                forward_to_warehouse_id: '',
                _town_query: '',
                _town_results: [],
                _town_open: false,
                _town_loading: false,
                _town_request: 0,
                _town_debounce: null,
                _town_linked: false,
                _town_context: '',
                _town_selected_display: null,
                _receipt_photo_files: [],
                ...overrides,
            };
        },

        openReceivingAddPackageModal() {
            if (this.isFinalized()) return;
            this.receivingAddPackageModal = this.receivingAddPackageDraft({
                open: true,
                description: `Package ${this.items.length + 1}`,
            });
        },

        closeReceivingAddPackageModal() {
            if (this.receivingAddPackageModal?.saving) return;

            if (this.receivingAddPackageModal?._town_debounce) {
                clearTimeout(this.receivingAddPackageModal._town_debounce);
            }

            this.receivingAddPackageModal = this.receivingAddPackageDraft();
        },

        loadReceiving() {
            window.location.reload();
        },

        setAddPackageFiles(event) {
            this.addPackageModal.files = Array.from(event?.target?.files || []);
        },

        setReceivingReceiptPhotos(pkg, files) {
            if (!pkg) return;
            pkg._receipt_photo_files = Array.from(files || []);
        },

        receivingReceiptPhotoNames(pkg) {
            const files = Array.isArray(pkg?._receipt_photo_files) ? pkg._receipt_photo_files : [];
            if (!files.length) return '';
            if (files.length === 1) return files[0].name;
            return `${files.length} receipt photos selected`;
        },

        addPackageTransferWarehouses() {
            return Array.isArray(config.transfer_warehouses) ? config.transfer_warehouses : [];
        },

        addPackageCanSave(modal) {
            if (!modal || modal.saving) return false;

            return Boolean(
                String(modal.description || '').trim()
                && Number(modal.quantity || 0) > 0
                && String(modal.delivery_recipient_phone || '').trim()
                && String(modal.delivery_town || modal._town_query || '').trim()
                && Array.isArray(modal._receipt_photo_files)
                && modal._receipt_photo_files.length > 0
            );
        },

        async addReceivingPackage() {
            const modal = this.receivingAddPackageModal;
            if (!modal || modal.saving || !config.add_package_url) return;
            const description = (modal.description || '').trim();
            if (!description) {
                window.showToast?.('Package description is required.', 'error');
                return;
            }

            if (!Number.isFinite(Number(modal.quantity)) || Number(modal.quantity) < 1) {
                window.showToast?.('Package quantity must be at least 1.', 'error');
                modal.quantity = 1;
                return;
            }

            modal.saving = true;
            try {
                const formData = new FormData();
                formData.append('description', description);
                formData.append('quantity', String(Math.max(Number(modal.quantity || 1), 1)));
                formData.append('delivery_recipient_name', modal.delivery_recipient_name || '');
                formData.append('delivery_recipient_phone', modal.delivery_recipient_phone || '');
                formData.append('delivery_region_id', modal.delivery_region_id || '');
                formData.append('delivery_district_id', modal.delivery_district_id || '');
                formData.append('delivery_town', modal.delivery_town || '');
                formData.append('delivery_landmark', modal.delivery_landmark || '');
                formData.append('delivery_instructions', modal.delivery_instructions || '');
                formData.append('delivery_method', modal.delivery_method || 'direct');
                if (modal.delivery_method !== 'bus_handoff' && modal.forward_to_warehouse_id) {
                    formData.append('forward_to_warehouse_id', modal.forward_to_warehouse_id);
                }
                formData.append('_token', csrfToken());
                (modal._receipt_photo_files || []).forEach((file) => formData.append('photos[]', file));

                const response = await fetch(config.add_package_url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to add package.');
                }

                this.applyReceivingPayload(result.data || {});
                this.closeReceivingAddPackageModal();
                window.showToast?.(result.message || 'Package added.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to add package.', 'error');
            } finally {
                modal.saving = false;
            }
        },

        async submitAddPackage() {
            return this.addReceivingPackage();
        },

        async autoGroupReceivingPackagesByPhone() {
            if (this.receiving.autoGrouping || !config.auto_group_by_phone_url) return;
            if (!this.receiving.canAutoGroup) {
                window.showToast?.(this.receiving.autoGroupLockReason || 'Auto-group is not available now.', 'warning');
                return;
            }

            this.receiving.autoGrouping = true;
            try {
                const response = await fetch(config.auto_group_by_phone_url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to group packages.');
                }

                this.applyReceivingPayload(result.data || {});
                window.showToast?.(result.message || 'Packages grouped by phone.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to group packages.', 'error');
            } finally {
                this.receiving.autoGrouping = false;
            }
        },

        openReceivingSplitModal(pkg) {
            const photos = pkg?.vendor_photos || [];
            if (!pkg?.can_split || photos.length < 2) {
                window.showToast?.(pkg?.split_lock_reason || 'A package needs at least two photos before it can be split.', 'warning');
                return;
            }

            this.splitModal = {
                open: true,
                saving: false,
                pkg,
                selectedPhotoIds: [],
            };
        },

        closeReceivingSplitModal() {
            if (this.splitModal.saving) return;
            this.splitModal = { open: false, saving: false, pkg: null, selectedPhotoIds: [] };
        },

        toggleSplitPhoto(photoId, checked) {
            const normalized = Number(photoId);
            const current = this.splitModal.selectedPhotoIds || [];
            this.splitModal.selectedPhotoIds = checked
                ? Array.from(new Set([...current, normalized]))
                : current.filter((id) => Number(id) !== normalized);
        },

        async submitSplitPackage() {
            const pkg = this.splitModal.pkg;
            if (!pkg || this.splitModal.saving || !config.split_package_url) return;
            if ((this.splitModal.selectedPhotoIds || []).length === 0) {
                window.showToast?.('Select at least one photo to move.', 'error');
                return;
            }

            this.splitModal.saving = true;
            try {
                const response = await fetch(endpointWithItem(config.split_package_url, pkg.shipment_item_id), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({ photo_ids: this.splitModal.selectedPhotoIds }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to split package.');
                }

                this.applyReceivingPayload(result.data || {});
                this.closeReceivingSplitModal();
                window.showToast?.(result.message || 'Package split.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to split package.', 'error');
                this.splitModal.saving = false;
            }
        },

        openFinalizeModal() {
            if (this.isFinalized()) return;
            if (!this.canReceive) {
                window.showToast?.('Cannot finalize — the rider has not picked up this shipment yet.', 'warning');
                return;
            }
            this.showFinalizeModal = true;
        },

        openFinalizeConfirm() {
            this.openFinalizeModal();
        },

        clearReceivingTown(item) {
            if (!item) return;
            clearTimeout(item._town_debounce);
            item._town_query = '';
            item._town_results = [];
            item._town_open = false;
            item._town_loading = false;
            item._town_linked = false;
            item._town_context = '';
            item._town_selected_display = null;
            item.delivery_town = '';
            item.delivery_region_id = '';
            item.delivery_district_id = '';
        },

        closeReceivingTownSearch(item) {
            if (!item) return;
            item._town_open = false;
        },

        updateReceivingTownQuery(item, value) {
            if (!item) return;
            item._town_query = value;
            item.delivery_town = String(value || '').trim();
            item.delivery_region_id = '';
            item.delivery_district_id = '';
            item._town_linked = false;
            item._town_context = '';
            item._town_selected_display = null;
            this.searchReceivingTownOptions(item);
        },

        async searchReceivingTownOptions(item) {
            if (!item) return;
            const query = (item._town_query || '').trim();
            clearTimeout(item._town_debounce);

            if (query.length < 2 || !config.townsSearchUrl) {
                item._town_results = [];
                item._town_open = false;
                item._town_loading = false;
                return;
            }

            const requestId = ++item._town_request;
            item._town_debounce = setTimeout(async () => {
                item._town_loading = true;
                try {
                    const url = new URL(config.townsSearchUrl, window.location.origin);
                    url.searchParams.set('search', query);
                    url.searchParams.set('active', '1');
                    url.searchParams.set('limit', '12');

                    const response = await fetch(url.toString(), {
                        headers: { Accept: 'application/json' },
                    });
                    const result = await response.json();
                    if (requestId !== item._town_request) return;

                    item._town_results = (result.data?.towns || []).map((town) => ({
                        ...town,
                        display: this.receivingTownDisplay(town.name, town.district_name, town.region_name, true),
                        context: this.receivingTownContext(town.district_name, town.region_name),
                    }));
                    item._town_open = item._town_results.length > 0;
                } catch (e) {
                    if (requestId === item._town_request) {
                        item._town_results = [];
                        item._town_open = false;
                    }
                } finally {
                    if (requestId === item._town_request) {
                        item._town_loading = false;
                    }
                }
            }, 300);
        },

        selectReceivingTownOption(item, town) {
            if (!item) return;
            const display = this.receivingTownDisplay(town.name, town.district_name, town.region_name, true);
            item.delivery_town = town.name || '';
            item.delivery_region_id = town.region_id ? String(town.region_id) : '';
            item.delivery_district_id = town.district_id ? String(town.district_id) : '';
            item.delivery_region_name = town.region_name || '';
            item.delivery_district_name = town.district_name || '';
            item._town_query = display;
            item._town_results = [];
            item._town_open = false;
            item._town_loading = false;
            item._town_linked = Boolean(town.region_id && town.district_id);
            item._town_context = this.receivingTownContext(town.district_name, town.region_name);
            item._town_selected_display = display;
        },

        async saveItem(itemId) {
            if (this.isFinalized()) {
                window.showToast?.('Receipt is finalized and cannot be edited.', 'warning');
                return;
            }
            if (!this.canReceive) {
                window.showToast?.('Cannot receive items — the rider has not picked up this shipment yet.', 'warning');
                return;
            }

            const item = this.items.find((row) => Number(row.shipment_item_id) === Number(itemId));
            if (!item) return;

            this.saving = true;
            try {
                const formData = new FormData();
	                formData.append('received_quantity', String(item.received_quantity ?? 0));
	                formData.append('damaged_quantity', String(item.damaged_quantity ?? 0));
	                formData.append('condition_status', item.condition_status || 'ok');
	                formData.append('description', item.description || '');
	                formData.append('notes', item.notes || '');
                formData.append('delivery_method', item.delivery_method || 'direct');
                if (this.isPerItemMode()) {
                    formData.append('delivery_recipient_name', item.delivery_recipient_name || '');
                    formData.append('delivery_recipient_phone', item.delivery_recipient_phone || '');
                    formData.append('delivery_region_id', item.delivery_region_id || '');
                    formData.append('delivery_district_id', item.delivery_district_id || '');
                    formData.append('delivery_town', item.delivery_town || '');
                    formData.append('delivery_landmark', item.delivery_landmark || '');
                    formData.append('delivery_instructions', item.delivery_instructions || '');
                }
                formData.append('_token', csrfToken());

                (this.pendingFiles[itemId] || []).forEach((file) => {
                    formData.append('photos[]', file);
                });

                (this.removePhotoMap[itemId] || []).forEach((photoId) => {
                    formData.append('remove_photo_ids[]', String(photoId));
                });

                const response = await fetch(endpointWithItem(config.save_item_url, itemId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to save item.');
                }

                if (result.data?.item) {
                    Object.assign(item, this.prepareReceivingTownState(result.data.item));
                }
                if (result.data?.receipt) {
                    this.receipt = result.data.receipt;
                }

                this.pendingFiles[itemId] = [];
                this.removePhotoMap[itemId] = [];
                window.showToast?.(result.message || 'Item saved successfully.', 'success');
                if (this.receiveModal.open && this.receiveModal.itemId === Number(itemId)) {
                    this.closeReceiveModal();
                }
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to save item.', 'error');
            } finally {
                this.saving = false;
            }
        },

        defaultPrintLabelCount(item) {
            const received = Number(item?.received_quantity || 0);
            const expected = Number(item?.expected_quantity || 0);
            const fallback = Number(item?.quantity || 0);
            const count = received || expected || fallback || 1;
            return Math.max(1, Math.min(500, count));
        },

        setPrintLabelCount(value) {
            const count = Number(value);
            this.printLabelModal.labelCount = Number.isFinite(count) ? Math.max(1, Math.min(500, count)) : 1;
        },

        openPrintLabelModal(item) {
            if (!item?.shipment_item_id) return;

            const labelCount = this.defaultPrintLabelCount(item);
            this.printLabelModal = {
                open: true,
                itemId: Number(item.shipment_item_id),
                trackingCode: item.tracking_code || item.barcode_value || item.description || 'Package labels',
                receivedQuantity: labelCount,
                labelCount,
                printing: false,
            };
        },

        closePrintLabelModal() {
            if (this.printLabelModal.printing) return;

            this.printLabelModal = {
                open: false,
                itemId: null,
                trackingCode: '',
                receivedQuantity: 1,
                labelCount: 1,
                printing: false,
            };
        },

        async printLabelFromModal() {
            if (!this.printLabelModal.itemId || this.printLabelModal.printing) return;

            this.setPrintLabelCount(this.printLabelModal.labelCount);
            this.printLabelModal.printing = true;
            const success = await this.printLabel(this.printLabelModal.itemId, this.printLabelModal.labelCount);
            this.printLabelModal.printing = false;

            if (success) {
                this.closePrintLabelModal();
            }
        },

        async printLabel(itemId, labelCount = 1) {
            this.saving = true;
            try {
                const response = await fetch(endpointWithItem(config.print_label_url, itemId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({ label_count: Math.max(1, Math.min(500, Number(labelCount || 1))) }),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to generate label.');
                }

                const html = result?.data?.label_html || '';
                if (!html) {
                    throw new Error('Label HTML payload is empty.');
                }

                const popup = window.open('', '_blank', 'width=900,height=650');
                if (!popup) {
                    throw new Error('Pop-up blocked. Please allow pop-ups to print labels.');
                }

                popup.document.open();
                popup.document.write(html);
                popup.document.close();

                const item = this.items.find((row) => Number(row.shipment_item_id) === Number(itemId));
                if (item) {
                    item.barcode_value = result?.data?.barcode_value || item.barcode_value;
                    item.label_count = Number(result?.data?.label_count || item.label_count || labelCount || 1);
                    item.barcode_print_count = Number(result?.data?.print_count || item.barcode_print_count || 0);
                }

                window.showToast?.(result.message || 'Label generated.', 'success');
                return true;
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to print label.', 'error');
                return false;
            } finally {
                this.saving = false;
            }
        },

        async finalizeReceipt() {
            if (this.isFinalized()) {
                this.showFinalizeModal = false;
                return;
            }

            if (this.hasDiscrepancies() && !this.canApproveDiscrepancy) {
                window.showToast?.('Discrepancy finalization requires warehouse manager approval.', 'error');
                return;
            }

            this.saving = true;
            try {
                const payload = {
                    notes: this.finalizeNotes || null,
                    approval_reason: this.approvalReason || null,
                };

                const response = await fetch(config.finalize_url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to finalize receipt.');
                }

                if (result.data?.receipt) {
                    this.receipt = result.data.receipt;
                } else {
                    this.receipt = { ...(this.receipt || {}), status: 'finalized' };
                }

                this.showFinalizeModal = false;
                window.showToast?.(result.message || 'Receipt finalized successfully.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to finalize receipt.', 'error');
            } finally {
                this.saving = false;
            }
        },

        openReceiveModal(itemId) {
            if (!this.canReceive) {
                window.showToast?.('Cannot receive items — the rider has not picked up this shipment yet.', 'warning');
                return;
            }
            const idx = this.items.findIndex((i) => Number(i.shipment_item_id) === Number(itemId));
            this.receiveModal = { open: true, itemId: Number(itemId), itemIndex: idx };
        },

        openReceivingPackageModal(pkg) {
            this.openReceiveModal(pkg?.shipment_item_id);
        },

        openPackageDetailsModal(pkg) {
            this.openReceivingPackageModal(pkg);
        },

        closeReceiveModal() {
            this.receiveModal = { open: false, itemId: null, itemIndex: -1 };
        },
    }));
}

if (window.Alpine) {
    registerWarehouseReceiptShowPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseReceiptShowPage);
}
