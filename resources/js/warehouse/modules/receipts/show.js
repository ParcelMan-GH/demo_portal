import { parseJsonAttribute } from '../../core/config.js';

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

        // Invoice state
        invoice: config.invoice || null,
        invoiceHistory: Array.isArray(config.invoice_history) ? config.invoice_history : [],
        invoiceModalOpen: false,
        invoiceUiError: '',
        invoiceForm: {
            pickup_fee: '',
            transport_fee: '',
            handling_fee: '',
            other_fee: '',
            notes: '',
            send_now: false,
            submitting: false,
        },
        invoiceTable: {
            search: '',
            sortBy: 'created_at',
            sortDirection: 'desc',
            page: 1,
            perPage: 10,
            statusFilter: '',
        },
        showCancelInvoiceModal: false,
        cancelInvoiceId: null,
        cancelInvoiceReason: '',
        cancelInvoiceLoading: false,
        isSuperAdmin: Boolean(config.is_hq_user || config.is_super_admin),
        canCreateInvoice: Boolean(config.can_create_invoice),
        canEditInvoice: Boolean(config.can_edit_invoice),
        canViewInvoice: Boolean(config.can_view_invoice),
        invoiceShowUrl: config.invoice_show_url || '',

        // Payments state
        paymentsLoaded: false,
        paymentsData: { payments: [], summary: { total_invoiced: 0, total_paid: 0, balance_due: 0 } },
        paymentSearch: '',
        paymentSortBy: 'payment_date',
        paymentSortDir: 'desc',
        paymentPage: 1,
        paymentPerPage: 10,
        paymentForm: {
            open: false,
            submitting: false,
            amount: '',
            payment_method: '',
            reference_number: '',
            notes: '',
            payment_date: '',
        },
        voidConfirm: {
            open: false,
            paymentId: null,
            loading: false,
        },

        receiveModal: { open: false, itemId: null, itemIndex: -1 },
        photoModal: { open: false, title: '', photos: [], index: 0 },

        init() {
            this.items = this.items.map((item) => this.prepareReceivingTownState(item));
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
            return this.assignment?.driver?.name || this.assignment?.driver_name || 'No driver assigned';
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

        receivingPackageCount() {
            return this.items.length;
        },

        receivingReceivedPackageCount() {
            return this.items.filter((item) => this.receivingPackageIsReceived(item)).length;
        },

        receivingPendingPackageCount() {
            return Math.max(this.receivingPackageCount() - this.receivingReceivedPackageCount(), 0);
        },

        receivingReceivedUnits() {
            return this.items.reduce((total, item) => {
                const received = Number(item?.received_quantity ?? 0);
                return total + (Number.isFinite(received) ? received : 0);
            }, 0);
        },

        receivingExpectedUnits() {
            return this.items.reduce((total, item) => total + this.receivingExpectedQuantity(item), 0);
        },

        receivingPendingUnits() {
            return this.items.reduce((total, item) => total + this.receivingPendingQuantity(item), 0);
        },

        discrepancyCount() {
            return this.items.filter((item) => (item.discrepancy_type || 'none') !== 'none').length;
        },

        receivingExpectedQuantity(item) {
            return Number(item?.expected_quantity ?? item?.driver_confirmed_quantity ?? item?.vendor_quantity ?? 0);
        },

        receivingObservedQuantity(item) {
            const received = Number(item?.received_quantity ?? 0);
            const damaged = Number(item?.damaged_quantity ?? 0);

            return (Number.isFinite(received) ? received : 0) + (Number.isFinite(damaged) ? damaged : 0);
        },

        receivingPendingQuantity(item) {
            return Math.max(this.receivingExpectedQuantity(item) - this.receivingObservedQuantity(item), 0);
        },

        receivingPackageIsReceived(item) {
            return Number(item?.received_quantity ?? 0) > 0;
        },

        receivingPackageActionLabel(item) {
            return this.receivingPackageIsReceived(item) ? 'Edit' : 'Receive';
        },

        receivingPackageStatusLabel(item) {
            const discrepancy = item?.discrepancy_type || 'none';
            if (discrepancy !== 'none') return discrepancy.replace(/_/g, ' ');
            if (this.receivingPackageIsReceived(item)) return 'Received';
            return 'Pending';
        },

        receivingPackageStatusTextClass(item) {
            const discrepancy = item?.discrepancy_type || 'none';
            if (discrepancy !== 'none') return 'text-amber-700';
            if (this.receivingPackageIsReceived(item)) return 'text-emerald-700';
            return 'text-slate-400';
        },

        receivingConditionLabel(condition) {
            switch (condition || 'ok') {
                case 'damaged':
                    return 'Damaged';
                case 'partial':
                    return 'Partial Damage';
                default:
                    return 'Good';
            }
        },

        receivingConditionTextClass(condition) {
            switch (condition || 'ok') {
                case 'damaged':
                    return 'text-amber-700';
                case 'partial':
                    return 'text-amber-700';
                default:
                    return 'text-emerald-700';
            }
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
            const driverPhotos = (item?.driver_photos || []).map((photo) => ({ url: photo?.url, label: 'Driver' }));
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

        openReceivingAddPackageModal() {
            if (this.isFinalized()) return;
            this.addPackageModal = {
                open: true,
                saving: false,
                description: `Package ${this.items.length + 1}`,
                quantity: 1,
                delivery_method: 'direct',
                files: [],
            };
        },

        closeReceivingAddPackageModal() {
            if (this.addPackageModal.saving) return;
            this.addPackageModal.open = false;
        },

        loadReceiving() {
            window.location.reload();
        },

        setAddPackageFiles(event) {
            this.addPackageModal.files = Array.from(event?.target?.files || []);
        },

        async submitAddPackage() {
            if (this.addPackageModal.saving || !config.add_package_url) return;
            const description = (this.addPackageModal.description || '').trim();
            if (!description) {
                window.showToast?.('Package description is required.', 'error');
                return;
            }

            this.addPackageModal.saving = true;
            try {
                const formData = new FormData();
                formData.append('description', description);
                formData.append('quantity', String(Math.max(Number(this.addPackageModal.quantity || 1), 1)));
                formData.append('delivery_method', this.addPackageModal.delivery_method || 'direct');
                formData.append('_token', csrfToken());
                (this.addPackageModal.files || []).forEach((file) => formData.append('photos[]', file));

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
                this.addPackageModal.open = false;
                window.showToast?.(result.message || 'Package added.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to add package.', 'error');
            } finally {
                this.addPackageModal.saving = false;
            }
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
                window.showToast?.('Cannot finalize — the driver has not picked up this shipment yet.', 'warning');
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
                window.showToast?.('Cannot receive items — the driver has not picked up this shipment yet.', 'warning');
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

        async printLabel(itemId) {
            this.saving = true;
            try {
                const response = await fetch(endpointWithItem(config.print_label_url, itemId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
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
                    item.barcode_print_count = Number(result?.data?.print_count || item.barcode_print_count || 0);
                }

                window.showToast?.(result.message || 'Label generated.', 'success');
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to print label.', 'error');
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

        // ─── Invoice helpers ─────────────────────────────────────────────────

        activeInvoice() {
            const active = ['pending', 'sent', 'accepted'];
            return this.invoiceHistory.find((r) => active.includes(r.status)) || null;
        },

        hasActiveInvoice() {
            return Boolean(this.activeInvoice());
        },

        invoiceStatusClass(status) {
            if (status === 'pending' || status === 'sent') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            if (status === 'accepted') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (status === 'rejected' || status === 'cancelled') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            return 'bg-slate-50 text-slate-600 ring-1 ring-slate-200';
        },

        invoiceStatusLabel(status) {
            switch (status) {
                case 'pending':   return 'Pending';
                case 'sent':      return 'Sent';
                case 'accepted':  return 'Accepted';
                case 'rejected':  return 'Rejected';
                case 'cancelled': return 'Cancelled';
                default: return status ? status.charAt(0).toUpperCase() + status.slice(1) : '-';
            }
        },

        openCreateInvoiceModal() {
            if (this.hasActiveInvoice()) {
                window.showToast?.('This shipment already has an active invoice.', 'error');
                return;
            }
            this.invoiceForm = { pickup_fee: '', transport_fee: '', handling_fee: '', other_fee: '', notes: '', send_now: false, submitting: false };
            this.invoiceUiError = '';
            this.invoiceModalOpen = true;
        },

        closeCreateInvoiceModal() {
            this.invoiceModalOpen = false;
            this.invoiceUiError = '';
        },

        async createInvoice() {
            this.invoiceUiError = '';
            this.invoiceForm.submitting = true;
            try {
                const response = await fetch(config.invoice_store_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        pickup_fee: this.invoiceForm.pickup_fee,
                        transport_fee: this.invoiceForm.transport_fee || 0,
                        handling_fee: this.invoiceForm.handling_fee || 0,
                        other_fee: this.invoiceForm.other_fee || 0,
                        notes: this.invoiceForm.notes || null,
                        send_now: this.invoiceForm.send_now ? 1 : 0,
                    }),
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to create invoice');

                window.showToast?.(data.message || 'Invoice created successfully', 'success');
                this.closeCreateInvoiceModal();
                window.location.reload();
            } catch (error) {
                console.error('Create invoice error:', error);
                this.invoiceUiError = error.message || 'Failed to create invoice';
                window.showToast?.(error.message || 'Failed to create invoice', 'error');
            } finally {
                this.invoiceForm.submitting = false;
            }
        },

        async sendInvoice(inv) {
            const url = inv?.send_url || this.invoice?.send_url;
            if (!url) return;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to send invoice');
                window.showToast?.(data.message || 'Invoice sent to vendor', 'success');
                window.location.reload();
            } catch (error) {
                window.showToast?.(error.message || 'Failed to send invoice', 'error');
            }
        },

        openCancelInvoiceModal(inv) {
            const target = inv || this.invoice;
            if (!target) return;
            this.cancelInvoiceId = target.id;
            this.cancelInvoiceReason = '';
            this.cancelInvoiceLoading = false;
            this.showCancelInvoiceModal = true;
        },

        async confirmCancelInvoice() {
            if (!this.cancelInvoiceId) return;
            this.cancelInvoiceLoading = true;

            const inv = this.invoiceHistory.find((r) => r.id === this.cancelInvoiceId) || this.invoice;
            const url = inv?.cancel_url;
            if (!url) {
                this.cancelInvoiceLoading = false;
                return;
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                    body: JSON.stringify({ cancel_reason: this.cancelInvoiceReason.trim() || null }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to cancel invoice');
                this.showCancelInvoiceModal = false;
                window.showToast?.(data.message || 'Invoice cancelled', 'success');
                window.location.reload();
            } catch (error) {
                this.cancelInvoiceLoading = false;
                window.showToast?.(error.message || 'Failed to cancel invoice', 'error');
            }
        },

        async adminAcceptInvoice(inv) {
            const target = inv || this.invoice;
            if (!target?.admin_accept_url) return;

            const confirmed = window.confirm(
                'Accept this invoice on behalf of the vendor?\n\n' +
                'This is an admin override. The invoice will be marked as accepted ' +
                'and the shipment will proceed to the next stage.'
            );
            if (!confirmed) return;

            const adminNotes = window.prompt('Optional notes for this override:') ?? '';
            if (adminNotes === null) return;

            try {
                const response = await fetch(target.admin_accept_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                    body: JSON.stringify({ admin_notes: adminNotes || null }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to accept invoice');
                window.showToast?.(data.message || 'Invoice accepted on behalf of vendor', 'success');
                window.location.reload();
            } catch (error) {
                window.showToast?.(error.message || 'Failed to accept invoice', 'error');
            }
        },

        // Invoice table helpers
        filteredInvoiceRows() {
            let rows = [...this.invoiceHistory];
            const q = (this.invoiceTable.search || '').trim().toLowerCase();
            if (q) {
                rows = rows.filter((r) =>
                    (r.invoice_number || '').toLowerCase().includes(q) ||
                    (r.status || '').toLowerCase().includes(q)
                );
            }
            if (this.invoiceTable.statusFilter) {
                rows = rows.filter((r) => r.status === this.invoiceTable.statusFilter);
            }
            const dir = this.invoiceTable.sortDirection === 'asc' ? 1 : -1;
            const key = this.invoiceTable.sortBy;
            rows.sort((a, b) => {
                const av = (a[key] ?? '').toString().toLowerCase();
                const bv = (b[key] ?? '').toString().toLowerCase();
                return av < bv ? -dir : av > bv ? dir : 0;
            });
            return rows;
        },

        paginatedInvoiceRows() {
            const rows = this.filteredInvoiceRows();
            const start = (this.invoiceTable.page - 1) * this.invoiceTable.perPage;
            return rows.slice(start, start + this.invoiceTable.perPage);
        },

        invoiceMeta() {
            const rows = this.filteredInvoiceRows();
            const total = rows.length;
            const lastPage = Math.max(1, Math.ceil(total / this.invoiceTable.perPage));
            const page = Math.min(this.invoiceTable.page, lastPage);
            const from = total === 0 ? 0 : (page - 1) * this.invoiceTable.perPage + 1;
            const to = Math.min(page * this.invoiceTable.perPage, total);
            return { total, page, lastPage, from, to };
        },

        sortInvoice(field) {
            if (this.invoiceTable.sortBy === field) {
                this.invoiceTable.sortDirection = this.invoiceTable.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.invoiceTable.sortBy = field;
                this.invoiceTable.sortDirection = 'asc';
            }
            this.invoiceTable.page = 1;
        },

        // ─── Payment helpers ─────────────────────────────────────────────────

        async loadPayments() {
            try {
                const url = config.payments_data_url;
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) throw new Error('Failed to load payments');
                const data = await response.json();
                this.paymentsData = data;
            } catch (e) {
                console.error('Failed to load payments:', e);
            } finally {
                this.paymentsLoaded = true;
            }
        },

        switchTab(tab) {
            this.activeTab = tab;
            if (tab === 'payments' && !this.paymentsLoaded) {
                this.loadPayments();
            }
        },

        filteredPayments() {
            let list = this.paymentsData.payments || [];
            if (this.paymentSearch) {
                const q = this.paymentSearch.toLowerCase();
                list = list.filter((p) =>
                    (p.payment_date || '').toLowerCase().includes(q) ||
                    (p.method_label || '').toLowerCase().includes(q) ||
                    (p.reference_number || '').toLowerCase().includes(q) ||
                    (p.invoice_number || '').toLowerCase().includes(q) ||
                    (p.recorded_by || '').toLowerCase().includes(q)
                );
            }
            const dir = this.paymentSortDir === 'asc' ? 1 : -1;
            const key = this.paymentSortBy;
            list = [...list].sort((a, b) => {
                const av = (a[key] ?? '').toString().toLowerCase();
                const bv = (b[key] ?? '').toString().toLowerCase();
                return av < bv ? -dir : av > bv ? dir : 0;
            });
            return list;
        },

        paginatedPayments() {
            const all = this.filteredPayments();
            const start = (this.paymentPage - 1) * this.paymentPerPage;
            return all.slice(start, start + this.paymentPerPage);
        },

        paymentLastPage() {
            return Math.max(1, Math.ceil(this.filteredPayments().length / this.paymentPerPage));
        },

        sortPayments(col) {
            if (this.paymentSortBy === col) {
                this.paymentSortDir = this.paymentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.paymentSortBy = col;
                this.paymentSortDir = 'desc';
            }
            this.paymentPage = 1;
        },

        openRecordPaymentModal() {
            this.paymentForm = {
                open: true,
                submitting: false,
                amount: '',
                payment_method: '',
                reference_number: '',
                notes: '',
                payment_date: new Date().toISOString().slice(0, 10),
            };
        },

        async submitPayment() {
            this.paymentForm.submitting = true;
            try {
                const response = await fetch(config.payments_store_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        amount: this.paymentForm.amount,
                        payment_method: this.paymentForm.payment_method,
                        reference_number: this.paymentForm.reference_number || null,
                        notes: this.paymentForm.notes || null,
                        payment_date: this.paymentForm.payment_date,
                    }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to record payment');
                window.showToast?.(data.message || 'Payment recorded', 'success');
                this.paymentForm = { open: false, submitting: false, amount: '', payment_method: '', reference_number: '', notes: '', payment_date: '' };
                await this.loadPayments();
            } catch (e) {
                console.error('Failed to record payment:', e);
                window.showToast?.(e.message || 'Failed to record payment', 'error');
            } finally {
                this.paymentForm.submitting = false;
            }
        },

        voidPayment(paymentId) {
            this.voidConfirm = { open: true, paymentId, loading: false };
        },

        async confirmVoidPayment() {
            this.voidConfirm.loading = true;
            try {
                const url = endpointWithId(config.payment_destroy_url, this.voidConfirm.paymentId);
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to void payment');
                this.voidConfirm = { open: false, paymentId: null, loading: false };
                window.showToast?.(data.message || 'Payment voided', 'success');
                await this.loadPayments();
            } catch (e) {
                console.error('Failed to void payment:', e);
                window.showToast?.(e.message || 'Failed to void payment', 'error');
                this.voidConfirm.loading = false;
            }
        },

        formatCurrency(value) {
            if (value == null || isNaN(Number(value))) return '0.00';
            return Number(value).toFixed(2);
        },

        paymentDownloadUrl(id) {
            return endpointWithId(config.payment_download_url, id);
        },

        paymentPrintUrl(id) {
            return endpointWithId(config.payment_print_url, id);
        },

        openReceiveModal(itemId) {
            if (!this.canReceive) {
                window.showToast?.('Cannot receive items — the driver has not picked up this shipment yet.', 'warning');
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
