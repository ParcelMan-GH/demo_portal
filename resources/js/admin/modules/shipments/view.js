/**
 * Admin shipment show page Alpine component
 * Extracted from Blade inline scripts and bundled via Vite.
 */

function shipmentShow() {
    return {
        config: {},
        shipment: {},
        canManage: false,
        invoice: null,
        invoiceHistory: [],
        assignment: null,
        assignmentHistory: [],
        assignmentUiError: '',
        assignmentActionLoading: false,
        assignmentHistoryModalOpen: false,
        trackingHistoryModalOpen: false,

        // Duplicate
        duplicating: false,

        // Custody
        custodyLoaded: false,
        custody: { loading: false, labels: [], creatingRun: false },

        // Receiving
        receivingLoaded: false,
        receivingLightbox: null,
        receivingSplitModal: { open: false, packageId: null, packageLabel: '', photos: [], selectedIds: [], saving: false },
        receivingPackageModal: { open: false, step: 1, packageId: null, packageLabel: '', pkg: null, savingDetails: false, savingReceive: false },
        receivingLabelPrintModal: { open: false, pkg: null, packageLabel: '', trackingCode: '', labelCount: 1, printing: false },
        receivingRemoveConfirm: { open: false, pkg: null, title: '', message: '', loading: false },
        sharedDestinationModal: { open: false, packageId: null, pkg: null, saving: false },
        receivingPhotosModal: { open: false, packageId: null, packageLabel: '', pkg: null, files: [], uploading: false },
        packageCustodyModal: { open: false, pkg: null, packageLabel: '' },
        receivingAddPackageModal: { open: false, saving: false },
        pickupEditModal: { open: false, saving: false, form: null },
        receiving: { loading: false, saving: false, detailsSaving: false, dropOffSaving: false, autoGrouping: false, completingPickup: false, canReceive: false, canAutoGroup: false, autoGroupLockReason: '', packages: [], receipt: null, assignmentId: null },
        finalizeNotes: '',
        approvalReason: '',
        canApproveReceivingDiscrepancy: false,

        // Charges
        chargesLoaded: false,
        chargesLoading: false,
        chargesData: [],
        chargesSummary: { revenue_total: 0, revenue_paid: 0, revenue_pending: 0, expense_total: 0, net: 0, outstanding_count: 0 },
        chargesDefaults: { pickup_fee: 0 },
        canManageCharges: false,
        chargeSubmitting: false,
        addChargeOpen: false,
        newCharge: { charge_type: 'pickup_fee', payer_type: 'vendor', due_stage: 'at_pickup', amount: '', shipment_item_id: '', notes: '', mark_paid: false, payment_method: 'cash', payment_reference: '' },
        pickupFeeModal: { open: false, chargeId: null, amount: '', notes: '', mark_paid: false, payment_method: 'cash', payment_reference: '', saving: false },
        markPaidOpen: false,
        markPaidCharge: null,
        markPaidForm: { payment_method: 'cash', payment_reference: '' },

        // Fulfillment type
        ftLoading: false,
        ftToast: '',
        ftToastType: 'success', // 'success' or 'error'
        _ftToastTimeout: null,

        activeTab: 'receiving',

        // Items state
        items: {
            data: [],
            loading: false
        },

        // Invoice form state
        invoiceForm: {
            pickup_fee: '',
            transport_fee: '',
            handling_fee: '',
            other_fee: '',
            notes: '',
            send_now: false,
            submitting: false
        },
        invoiceModalOpen: false,
        invoiceDetailModalOpen: false,
        invoiceDetail: null,
        invoiceUiError: '',
        invoiceTable: {
            search: '',
            statusFilter: '',
            statusFilterLabel: 'All statuses',
            sortBy: 'created_at',
            sortDirection: 'desc',
            page: 1,
            perPage: 10,
            columns: [
                { key: 'invoice_number', label: 'Invoice Number' },
                { key: 'status', label: 'Status' },
                { key: 'is_active', label: 'Active' },
                { key: 'total_amount', label: 'Total' },
                { key: 'created_at', label: 'Created' },
                { key: 'actions', label: 'Actions' },
            ],
            visibleColumns: {
                invoice_number: true,
                status: true,
                is_active: true,
                total_amount: true,
                created_at: true,
                actions: true,
            }
        },

        // Assignment form state
        assignmentForm: {
            driver_id: '',
            target_warehouse_id: '',
            notes: '',
            submitting: false,
            loadingDrivers: false,
            loadingWarehouses: false
        },

        // Available drivers
        availableDrivers: [],
        availableWarehouses: [],

        // Modal states
        assignDriverModalOpen: false,
        showUnassignModal: false,
        unassignReason: '',
        showCancelInvoiceModal: false,
        cancelInvoiceReason: '',
        cancelInvoiceId: null,
        cancelInvoiceLoading: false,

        // Edit assignment form state
        editAssignmentOpen: false,
        editAssignmentForm: {
            driver_id: '',
            target_warehouse_id: '',
            submitting: false,
            loadingDrivers: false,
            loadingWarehouses: false,
        },
        availableDriversForEdit: [],

        // Tracking state
        tracking: {
            data: [],
            items: [],
            loading: false,
            itemsExpanded: {},
        },

        // Payments tab
        paymentsLoaded: false,
        paymentsData: { payments: [], summary: { total_invoiced: 0, total_paid: 0, balance_due: 0 } },
        paymentSearch: '',
        paymentSortBy: 'payment_date',
        paymentSortDir: 'desc',
        paymentPage: 1,
        paymentPerPage: 10,
        paymentColumns: [
            { key: 'payment_date', label: 'Date' },
            { key: 'amount', label: 'Amount' },
            { key: 'method', label: 'Method' },
            { key: 'reference', label: 'Reference' },
            { key: 'invoice', label: 'Invoice' },
            { key: 'recorded_by', label: 'Recorded By' },
            { key: 'notes', label: 'Notes' },
            { key: 'actions', label: 'Actions' },
        ],
        paymentVisibleColumns: {
            payment_date: true,
            amount: true,
            method: true,
            reference: true,
            invoice: true,
            recorded_by: true,
            notes: true,
            actions: true,
        },
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
        isSuperAdmin: false,

        shipmentDestinationMode() {
            const mode = this.shipment?.destination_mode;
            if (!mode) return 'single';
            if (typeof mode === 'string') return mode;
            if (typeof mode === 'object' && mode.value) return mode.value;
            return 'single';
        },

        isPerItemMode() {
            return this.shipmentDestinationMode() === 'per_item';
        },

        shipmentDestinationModeLabel() {
            return this.isPerItemMode() ? 'Per Item Destination' : 'Single Destination';
        },

        shipmentDestinationModeBadgeClass() {
            return this.isPerItemMode()
                ? 'bg-violet-100 text-violet-700'
                : 'bg-sky-100 text-sky-700';
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

        deliveryLocationSummary() {
            if (this.shipment?.delivery_region_id && this.shipment?.delivery_district_id) {
                const parts = [
                    this.shipment?.delivery_region?.name,
                    this.shipment?.delivery_district?.name,
                    this.shipment?.delivery_town
                ].filter(Boolean);
                if (parts.length > 0) return parts.join(', ');
            }

            if (this.shipment?.delivery_latitude && this.shipment?.delivery_longitude) {
                return `${this.shipment.delivery_latitude}, ${this.shipment.delivery_longitude}`;
            }

            if (this.shipment?.delivery_gh_post_address) {
                return this.shipment.delivery_gh_post_address;
            }

            return '-';
        },

        itemDestinationTitle(item) {
            if (!item) return '-';
            if (this.isPerItemMode()) {
                return item.delivery_recipient_name || '-';
            }
            return this.shipment?.delivery_recipient_name || '-';
        },

        itemDestinationSubtitle(item) {
            if (!item) return '-';
            if (this.isPerItemMode()) {
                return item.delivery_recipient_phone || '-';
            }
            return this.shipment?.delivery_recipient_phone || '-';
        },

        itemLocationTitle(item) {
            if (!item) return '-';
            if (this.isPerItemMode()) {
                return item.delivery_location_title || '-';
            }
            return this.shipment?.delivery_region?.name || 'Shared shipment destination';
        },

        itemLocationSubtitle(item) {
            if (!item) return '-';
            if (this.isPerItemMode()) {
                return item.delivery_location_subtitle || '-';
            }

            const shared = [
                this.shipment?.delivery_district?.name,
                this.shipment?.delivery_town
            ].filter(Boolean).join(', ');

            return shared || '-';
        },

        fulfillmentTypeLabel() {
            const ft = this.shipment?.fulfillment_type || 'warehouse';
            return { warehouse: 'Warehouse Delivery', direct: 'Direct Delivery' }[ft] || 'Warehouse Delivery';
        },

        canChangeFulfillmentType() {
            if (!this.canManage) return false;
            const blocked = ['picked_up', 'at_warehouse', 'sorted', 'in_transit', 'at_destination', 'out_for_delivery', 'delivered', 'cancelled'];
            return !blocked.includes(this.shipment?.status);
        },

        showFtToast(message, type = 'success') {
            this.ftToast = message;
            this.ftToastType = type;
            clearTimeout(this._ftToastTimeout);
            this._ftToastTimeout = setTimeout(() => { this.ftToast = ''; }, 3500);
        },

        async changeFulfillmentType(newType) {
            if (newType === this.shipment?.fulfillment_type) return;
            this.ftLoading = true;
            try {
                const response = await fetch(this.config.updateFulfillmentTypeEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ fulfillment_type: newType }),
                });
                const result = await response.json();
                if (result.success) {
                    this.shipment.fulfillment_type = result.fulfillment_type;
                    this.showFtToast('Fulfillment type changed to ' + this.fulfillmentTypeLabel());
                } else {
                    this.showFtToast(result.message || 'Failed to update.', 'error');
                }
            } catch (e) {
                this.showFtToast('An error occurred. Please try again.', 'error');
            }
            this.ftLoading = false;
        },

        async duplicateShipment() {
            if (!confirm('Create a duplicate of this shipment as a draft? All packages and photos will be copied.')) return;
            this.duplicating = true;
            try {
                const url = this.config.duplicateEndpoint;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });
                const result = await response.json();
                if (result.success && result.data?.edit_url) {
                    window.location.href = result.data.edit_url;
                } else {
                    alert(result.message || 'Failed to duplicate.');
                }
            } catch (e) {
                alert('An error occurred.');
            }
            this.duplicating = false;
        },

        async loadCustody() {
            this.custody.loading = true;
            this.custodyLoaded = true;
            try {
                const url = this.config.custodyDataEndpoint;
                if (!url) { console.error('custodyDataEndpoint not configured'); this.custody.loading = false; return; }
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) { console.error('Custody data fetch failed:', response.status); this.custody.loading = false; return; }
                const result = await response.json();
                if (result.success) {
                    this.custody.labels = Array.isArray(result.data?.labels) ? result.data.labels : [];
                }
            } catch (e) { console.error('Failed to load custody data', e); }
            this.custody.loading = false;
        },

        custodyLabels() {
            return Array.isArray(this.custody.labels) ? this.custody.labels : [];
        },

        custodyDriverGroups() {
            const drivers = {};
            this.custodyLabels().forEach(l => {
                if (l && l.current_driver && l.current_driver.id) {
                    const id = l.current_driver.id;
                    if (!drivers[id]) {
                        drivers[id] = {
                            driver_id: id,
                            name: l.current_driver.name || 'Unknown driver',
                            phone: l.current_driver.phone || '',
                            count: 0,
                        };
                    }
                    drivers[id].count++;
                }
            });
            return Object.values(drivers);
        },

        async createRunFromClaims(driverId) {
            if (!confirm('Create a delivery run from this driver\'s claimed packages?')) return;
            this.custody.creatingRun = true;
            try {
                const response = await fetch(this.config.createRunFromClaimsEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        driver_id: driverId,
                        warehouse_id: this.shipment.pickup_assignment?.target_warehouse?.id || this.config.shipment?.pickup_assignment?.target_warehouse?.id,
                    }),
                });
                const result = await response.json();
                if (result.success) {
                    window.showToast?.('Delivery run created: ' + result.data.run_number + ' with ' + result.data.stops_count + ' stop(s).', 'success');
                    this.loadCustody();
                    this.loadReceiving();
                } else {
                    window.showToast?.(result.message || 'Failed to create run.', 'error');
                }
            } catch (e) { window.showToast?.('Error creating run.', 'error'); }
            this.custody.creatingRun = false;
        },

        async adminCompletePickup() {
            this.receiving.completingPickup = true;
            try {
                const response = await fetch(this.config.adminCompletePickupEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const result = await response.json();
                if (result.success) {
                    window.showToast?.('Pickup marked as completed. You can now receive packages.', 'success');
                    this.receiving.canReceive = true;
                    this.loadReceiving();
                } else {
                    window.showToast?.(result.message || 'Failed to complete pickup.', 'error');
                }
            } catch (e) { window.showToast?.('Error completing pickup.', 'error'); }
            this.receiving.completingPickup = false;
        },

        // ── Charges ──────────────────────────────────────────────────────
        async loadCharges(silent = false) {
            if (!silent) this.chargesLoading = true;
            this.chargesLoaded = true;
            try {
                const res = await fetch(this.config.chargesIndexEndpoint, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    if (res.status === 403) {
                        this.canManageCharges = false;
                        window.showToast?.('You don\'t have permission to view charges.', 'error');
                        return;
                    }
                    throw new Error('Failed to load charges');
                }
                const json = await res.json();
                this.chargesData = json.data || [];
                this.chargesSummary = json.summary || this.chargesSummary;
                this.chargesDefaults = json.defaults || this.chargesDefaults;
            } catch (e) {
                window.showToast?.('Failed to load charges.', 'error');
            }
            if (!silent) this.chargesLoading = false;
        },

        hasPickupFee() {
            return this.chargesData.some(c =>
                c.charge_type === 'pickup_fee' && !['cancelled'].includes(c.status)
            );
        },

        pickupFeeCharge() {
            const charges = Array.isArray(this.chargesData) ? this.chargesData : [];
            return charges.find(c => c.charge_type === 'pickup_fee' && !['cancelled'].includes(c.status)) || null;
        },

        pickupFeeButtonLabel() {
            const charge = this.pickupFeeCharge();
            if (!charge) return 'Set Pickup Fee';
            const amount = Number(charge.amount || 0).toFixed(2);
            return `Pickup Fee: ${charge.currency || 'GHS'} ${amount}`;
        },

        pickupFeeButtonClass() {
            const charge = this.pickupFeeCharge();
            if (!charge) return 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border-amber-500/30';
            if (charge.status === 'paid') return 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border-emerald-500/30';
            return 'bg-slate-500/20 hover:bg-slate-500/30 text-slate-300 border-slate-500/30';
        },

        pickupFeeIsPaid() {
            return this.pickupFeeCharge()?.status === 'paid';
        },

        openPickupFeeModal() {
            const charge = this.pickupFeeCharge();
            this.pickupFeeModal = {
                open: true,
                chargeId: charge?.id || null,
                amount: charge ? Number(charge.amount || 0).toFixed(2) : '',
                notes: charge?.notes || '',
                mark_paid: charge?.status === 'paid',
                payment_method: charge?.payment_method || 'cash',
                payment_reference: charge?.payment_reference || '',
                saving: false,
            };
        },

        closePickupFeeModal() {
            if (this.pickupFeeModal.saving) return;
            this.pickupFeeModal.open = false;
        },

        async submitPickupFee() {
            if (this.pickupFeeModal.amount === '' || this.pickupFeeModal.amount === null) return;

            this.pickupFeeModal.saving = true;
            this.chargeSubmitting = true;

            try {
                const existing = this.pickupFeeCharge();
                const csrf = document.querySelector('meta[name=csrf-token]').content;
                const amount = Number(this.pickupFeeModal.amount || 0);
                const baseBody = {
                    charge_type: 'pickup_fee',
                    payer_type: 'vendor',
                    due_stage: 'at_pickup',
                    amount,
                    notes: this.pickupFeeModal.notes || null,
                };

                let res;
                let json;

                if (existing) {
                    const url = this.config.chargesUpdateEndpointTemplate.replace('__CHARGE__', existing.id);
                    const updateBody = { ...baseBody };

                    if (existing.status === 'paid') {
                        updateBody.payment_method = this.pickupFeeModal.payment_method || 'cash';
                        updateBody.payment_reference = this.pickupFeeModal.payment_reference || null;
                    }

                    res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(updateBody),
                    });
                    json = await res.json().catch(() => ({}));

                    if (!res.ok || json.success === false) {
                        window.showToast?.(json.message || 'Failed to update pickup fee.', 'error');
                        return;
                    }

                    if (this.pickupFeeModal.mark_paid && existing.status !== 'paid') {
                        const markPaidUrl = this.config.chargesMarkPaidEndpointTemplate.replace('__CHARGE__', existing.id);
                        const paidRes = await fetch(markPaidUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                payment_method: this.pickupFeeModal.payment_method || 'cash',
                                payment_reference: this.pickupFeeModal.payment_reference || null,
                            }),
                        });
                        const paidJson = await paidRes.json().catch(() => ({}));
                        if (!paidRes.ok || paidJson.success === false) {
                            window.showToast?.(paidJson.message || 'Pickup fee was updated, but could not be marked paid.', 'error');
                            await this.loadCharges(true);
                            return;
                        }
                    }
                } else {
                    const createBody = {
                        ...baseBody,
                        shipment_item_id: null,
                    };

                    if (this.pickupFeeModal.mark_paid) {
                        createBody.status = 'paid';
                        createBody.payment_method = this.pickupFeeModal.payment_method || 'cash';
                        createBody.payment_reference = this.pickupFeeModal.payment_reference || null;
                    }

                    res = await fetch(this.config.chargesStoreEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(createBody),
                    });
                    json = await res.json().catch(() => ({}));

                    if (!res.ok || json.success === false) {
                        window.showToast?.(json.message || 'Failed to set pickup fee.', 'error');
                        return;
                    }
                }

                window.showToast?.(existing ? 'Pickup fee updated.' : 'Pickup fee set.', 'success');
                this.pickupFeeModal.open = false;
                await this.loadCharges(true);
            } catch (e) {
                window.showToast?.('Network error.', 'error');
            } finally {
                this.pickupFeeModal.saving = false;
                this.chargeSubmitting = false;
            }
        },

        formatChargeType(type) {
            const map = {
                pickup_fee: 'Pickup Fee',
                delivery_fee: 'Delivery Fee',
                station_fee: 'Station Fee',
                handling_fee: 'Handling Fee',
                other: 'Other',
            };
            return map[type] || type;
        },

        formatStage(stage) {
            const map = {
                at_pickup: 'At pickup',
                at_receiving: 'At receiving',
                before_delivery: 'Before delivery',
                at_delivery: 'At delivery',
                at_handoff: 'At handoff',
            };
            return map[stage] || stage;
        },

        applyChargeTypeDefaults() {
            // Set sensible defaults for each charge type; user can still change them.
            const defaults = {
                pickup_fee:   { payer_type: 'vendor',    due_stage: 'at_pickup' },
                station_fee:  { payer_type: 'parcelman', due_stage: 'at_handoff' },
            };
            const d = defaults[this.newCharge.charge_type];
            if (d) {
                this.newCharge.payer_type = d.payer_type;
                this.newCharge.due_stage = d.due_stage;
            }
        },

        openAddCharge() {
            this.newCharge = {
                charge_type: 'pickup_fee',
                payer_type: 'vendor',
                due_stage: 'at_pickup',
                amount: '',
                shipment_item_id: '',
                notes: '',
                mark_paid: false,
                payment_method: 'cash',
                payment_reference: '',
            };
            this.addChargeOpen = true;
        },

        async submitAddCharge() {
            if (!this.newCharge.amount) return;
            this.chargeSubmitting = true;
            try {
                const body = {
                    charge_type: this.newCharge.charge_type,
                    payer_type: this.newCharge.payer_type,
                    due_stage: this.newCharge.due_stage,
                    amount: Number(this.newCharge.amount),
                    shipment_item_id: null,
                    notes: this.newCharge.notes || null,
                };
                if (this.newCharge.mark_paid) {
                    body.status = 'paid';
                    body.payment_method = this.newCharge.payment_method;
                    body.payment_reference = this.newCharge.payment_reference || null;
                }
                const res = await fetch(this.config.chargesStoreEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok) {
                    window.showToast?.(json.message || 'Charge added.', 'success');
                    this.addChargeOpen = false;
                    await this.loadCharges(true);
                } else {
                    window.showToast?.(json.message || 'Failed to add charge.', 'error');
                }
            } catch (e) {
                window.showToast?.('Network error.', 'error');
            }
            this.chargeSubmitting = false;
        },

        async seedPickupFee() {
            try {
                const res = await fetch(this.config.chargesSeedPickupFeeEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok) {
                    window.showToast?.(json.message || 'Pickup fee added.', 'success');
                    await this.loadCharges(true);
                } else {
                    window.showToast?.(json.message || 'Could not add pickup fee. Set a default in Settings → Revenue & Pricing.', 'error');
                }
            } catch (e) {
                window.showToast?.('Network error.', 'error');
            }
        },

        openMarkPaid(charge) {
            this.markPaidCharge = charge;
            this.markPaidForm = { payment_method: 'cash', payment_reference: '' };
            this.markPaidOpen = true;
        },

        async submitMarkPaid() {
            if (!this.markPaidCharge || !this.markPaidForm.payment_method) return;
            this.chargeSubmitting = true;
            try {
                const url = this.config.chargesMarkPaidEndpointTemplate.replace('__CHARGE__', this.markPaidCharge.id);
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_method: this.markPaidForm.payment_method,
                        payment_reference: this.markPaidForm.payment_reference || null,
                    }),
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success !== false) {
                    window.showToast?.(json.message || 'Marked paid.', 'success');
                    this.markPaidOpen = false;
                    await this.loadCharges(true);
                } else {
                    window.showToast?.(json.message || 'Failed to mark paid.', 'error');
                }
            } catch (e) {
                window.showToast?.('Network error.', 'error');
            }
            this.chargeSubmitting = false;
        },

        async waiveCharge(charge) {
            const reason = prompt('Reason for waiving this charge (optional):') ?? null;
            try {
                const url = this.config.chargesWaiveEndpointTemplate.replace('__CHARGE__', charge.id);
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ reason }),
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success !== false) {
                    window.showToast?.(json.message || 'Charge waived.', 'success');
                    await this.loadCharges(true);
                } else {
                    window.showToast?.(json.message || 'Failed to waive.', 'error');
                }
            } catch (e) {
                window.showToast?.('Network error.', 'error');
            }
        },

        async cancelCharge(charge) {
            if (!confirm('Cancel this charge? This cannot be undone.')) return;
            try {
                const url = this.config.chargesCancelEndpointTemplate.replace('__CHARGE__', charge.id);
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.success !== false) {
                    window.showToast?.(json.message || 'Charge cancelled.', 'success');
                    await this.loadCharges(true);
                } else {
                    window.showToast?.(json.message || 'Failed to cancel.', 'error');
                }
            } catch (e) {
                window.showToast?.('Network error.', 'error');
            }
        },

        receivingTownContext(districtName, regionName) {
            return [districtName, regionName].filter(Boolean).join(', ');
        },

        receivingTownDisplay(town, districtName = '', regionName = '', isLinked = false) {
            if (!town) return '';
            return isLinked ? [town, districtName, regionName].filter(Boolean).join(', ') : town;
        },

        buildPickupEditForm() {
            const town = this.shipment?.pickup_town || '';
            const regionId = this.shipment?.pickup_region_id ? String(this.shipment.pickup_region_id) : '';
            const districtId = this.shipment?.pickup_district_id ? String(this.shipment.pickup_district_id) : '';
            const regionName = this.shipment?.pickup_region?.name || '';
            const districtName = this.shipment?.pickup_district?.name || '';
            const isLinked = Boolean(town && regionId && districtId);

            return {
                contact_name: this.shipment?.pickup_contact_name || '',
                contact_phone: this.shipment?.pickup_contact_phone || '',
                region_id: regionId,
                region_name: regionName,
                district_id: districtId,
                district_name: districtName,
                town,
                landmark: this.shipment?.pickup_landmark || '',
                instructions: this.shipment?.pickup_instructions || '',
                _town_query: town,
                _town_results: [],
                _town_open: false,
                _town_loading: false,
                _town_request: 0,
                _town_debounce: null,
                _town_linked: isLinked,
                _town_context: isLinked ? this.receivingTownContext(districtName, regionName) : '',
                _town_selected_display: isLinked ? town : null,
            };
        },

        openPickupEditModal() {
            this.pickupEditModal = {
                open: true,
                saving: false,
                form: this.buildPickupEditForm(),
            };
        },

        closePickupEditModal() {
            if (this.pickupEditModal.saving) return;

            const form = this.pickupEditModal.form;
            if (form?._town_debounce) {
                clearTimeout(form._town_debounce);
            }

            this.pickupEditModal = {
                open: false,
                saving: false,
                form: null,
            };
        },

        closePickupTownSearch() {
            if (!this.pickupEditModal.form) return;
            this.pickupEditModal.form._town_open = false;
        },

        clearPickupTown() {
            const form = this.pickupEditModal.form;
            if (!form) return;

            clearTimeout(form._town_debounce);
            form._town_query = '';
            form._town_results = [];
            form._town_open = false;
            form._town_loading = false;
            form._town_linked = false;
            form._town_context = '';
            form._town_selected_display = null;
            form.town = '';
            form.region_id = '';
            form.region_name = '';
            form.district_id = '';
            form.district_name = '';
        },

        updatePickupTownQuery(value) {
            const form = this.pickupEditModal.form;
            if (!form) return;

            form._town_query = value;
            form.town = value.trim();
            form.region_id = '';
            form.region_name = '';
            form.district_id = '';
            form.district_name = '';
            form._town_linked = false;
            form._town_context = '';
            form._town_selected_display = null;
            this.searchPickupTownOptions();
        },

        async searchPickupTownOptions() {
            const form = this.pickupEditModal.form;
            if (!form) return;

            const query = (form._town_query || '').trim();
            clearTimeout(form._town_debounce);

            if (query.length < 2) {
                form._town_results = [];
                form._town_open = false;
                form._town_loading = false;
                return;
            }

            const requestId = ++form._town_request;
            form._town_debounce = setTimeout(async () => {
                form._town_loading = true;
                try {
                    const url = new URL(this.config.townsSearchUrl, window.location.origin);
                    url.searchParams.set('search', query);
                    url.searchParams.set('active', '1');
                    url.searchParams.set('limit', '12');

                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const result = await response.json();
                    if (requestId !== form._town_request) return;

                    form._town_results = (result.data?.towns || []).map(town => ({
                        ...town,
                        display: this.receivingTownDisplay(town.name, town.district_name, town.region_name, true),
                        context: this.receivingTownContext(town.district_name, town.region_name),
                    }));
                    form._town_open = form._town_results.length > 0;
                } catch (e) {
                    if (requestId === form._town_request) {
                        form._town_results = [];
                        form._town_open = false;
                    }
                } finally {
                    if (requestId === form._town_request) {
                        form._town_loading = false;
                    }
                }
            }, 300);
        },

        selectPickupTownOption(town) {
            const form = this.pickupEditModal.form;
            if (!form) return;

            form.town = town.name || '';
            form.region_id = town.region_id ? String(town.region_id) : '';
            form.region_name = town.region_name || '';
            form.district_id = town.district_id ? String(town.district_id) : '';
            form.district_name = town.district_name || '';
            form._town_query = town.name || '';
            form._town_results = [];
            form._town_open = false;
            form._town_loading = false;
            form._town_linked = Boolean(town.region_id && town.district_id);
            form._town_context = this.receivingTownContext(town.district_name, town.region_name);
            form._town_selected_display = town.name || '';
        },

        async savePickupFromReceiving() {
            const form = this.pickupEditModal.form;
            if (!form || this.pickupEditModal.saving || !this.config.saveUrl) return;

            this.pickupEditModal.saving = true;
            try {
                const response = await fetch(this.config.saveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        destination_mode: this.shipmentDestinationMode(),
                        pickup_contact_name: form.contact_name || null,
                        pickup_contact_phone: form.contact_phone || null,
                        pickup_region_id: form.region_id || null,
                        pickup_district_id: form.district_id || null,
                        pickup_town: form.town || null,
                        pickup_landmark: form.landmark || null,
                        pickup_instructions: form.instructions || null,
                    }),
                });
                const result = await response.json();

                if (result.success) {
                    const pickup = result.data?.pickup || {};
                    this.shipment.pickup_contact_name = (pickup.contact_name ?? form.contact_name) || null;
                    this.shipment.pickup_contact_phone = (pickup.contact_phone ?? form.contact_phone) || null;
                    this.shipment.pickup_region_id = pickup.region_id ?? (form.region_id ? Number(form.region_id) : null);
                    this.shipment.pickup_district_id = pickup.district_id ?? (form.district_id ? Number(form.district_id) : null);
                    this.shipment.pickup_town = (pickup.town ?? form.town) || null;
                    this.shipment.pickup_landmark = (pickup.landmark ?? form.landmark) || null;
                    this.shipment.pickup_instructions = (pickup.instructions ?? form.instructions) || null;
                    this.shipment.pickup_region = this.shipment.pickup_region_id
                        ? { id: this.shipment.pickup_region_id, name: (pickup.region_name ?? form.region_name) || '' }
                        : null;
                    this.shipment.pickup_district = this.shipment.pickup_district_id
                        ? { id: this.shipment.pickup_district_id, name: (pickup.district_name ?? form.district_name) || '' }
                        : null;
                    window.showToast?.(result.message || 'Pickup details saved.', 'success');
                    this.pickupEditModal = {
                        open: false,
                        saving: false,
                        form: null,
                    };
                    return;
                } else {
                    window.showToast?.(result.message || 'Failed to save pickup details.', 'error');
                }
            } catch (e) {
                window.showToast?.('Error saving pickup details.', 'error');
            }

            this.pickupEditModal.saving = false;
        },

        receivingExpectedQuantity(pkg) {
            const expected = Number(pkg?.expected_quantity ?? pkg?.driver_confirmed_quantity ?? pkg?.vendor_quantity ?? 0);
            return Number.isFinite(expected) ? expected : 0;
        },

        receivingObservedQuantity(pkg) {
            const received = Number(pkg?.received_quantity ?? 0);
            const damaged = Number(pkg?.damaged_quantity ?? 0);

            return (Number.isFinite(received) ? received : 0) + (Number.isFinite(damaged) ? damaged : 0);
        },

        receivingPackageCount() {
            if (Array.isArray(this.receiving.packages) && this.receiving.packages.length > 0) {
                return this.receiving.packages.length;
            }

            if (Array.isArray(this.shipment?.items)) {
                return this.shipment.items.length;
            }

            return 0;
        },

        receivingReceivedUnits() {
            return (this.receiving.packages || []).reduce((total, pkg) => {
                const received = Number(pkg?.received_quantity ?? 0);
                return total + (Number.isFinite(received) ? received : 0);
            }, 0);
        },

        receivingAllPackagesReceived() {
            const packages = this.receiving.packages || [];
            if (packages.length === 0) return false;

            return packages.every((pkg) => {
                return this.receivingObservedQuantity(pkg) >= this.receivingExpectedQuantity(pkg);
            });
        },

        receivingIsFinalized() {
            return this.receiving.receipt?.status === 'finalized';
        },

        canFinalizeReceiving() {
            return !this.receiving.saving
                && this.receivingAllPackagesReceived()
                && !this.receivingIsFinalized();
        },

        finalizeReceivingButtonLabel() {
            if (this.receiving.saving) return 'Finalizing...';
            if (this.receivingIsFinalized()) return 'Finalized';
            return 'Finalize';
        },

        receivingPendingUnits() {
            return (this.receiving.packages || []).reduce((total, pkg) => {
                const expected = this.receivingExpectedQuantity(pkg);
                const observed = this.receivingObservedQuantity(pkg);
                return total + Math.max(expected - observed, 0);
            }, 0);
        },

        receivingDiscrepancyType(pkg) {
            const expected = this.receivingExpectedQuantity(pkg);
            const received = Number(pkg?.received_quantity ?? 0);
            const damaged = Number(pkg?.damaged_quantity ?? 0);
            const normalizedReceived = Number.isFinite(received) ? received : 0;
            const normalizedDamaged = Number.isFinite(damaged) ? damaged : 0;
            const totalObserved = normalizedReceived + normalizedDamaged;
            const hasMissing = totalObserved < expected;
            const hasExcess = totalObserved > expected;
            const hasDamaged = normalizedDamaged > 0;

            if (!hasMissing && !hasExcess && !hasDamaged) {
                return 'none';
            }

            if (hasMissing && !hasDamaged && !hasExcess) {
                return 'missing';
            }

            if (hasExcess && !hasDamaged && !hasMissing) {
                return 'excess';
            }

            if (hasDamaged && !hasMissing && !hasExcess) {
                return 'damaged';
            }

            return 'mixed';
        },

	        receivingDiscrepancyLabel(type) {
	            switch (String(type || 'none')) {
	                case 'missing':
	                    return 'Missing';
                case 'excess':
                    return 'Excess';
                case 'damaged':
                    return 'Damaged';
                case 'mixed':
                    return 'Mixed';
                default:
	                    return 'No discrepancy';
	            }
	        },

	        receivingPendingQuantity(pkg) {
	            return Math.max(this.receivingExpectedQuantity(pkg) - this.receivingObservedQuantity(pkg), 0);
	        },

	        receivingPackageIsReceived(pkg) {
	            return Number(pkg?.received_quantity ?? 0) > 0;
	        },

	        receivingPackageStatusLabel(pkg) {
	            if (this.receivingPackageIsReceived(pkg)) return 'Received';
	            return this.receivingPendingQuantity(pkg) > 0 ? 'Pending' : 'Ready';
	        },

	        receivingPackageStatusClass(pkg) {
	            if (this.receivingPackageIsReceived(pkg)) {
	                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
	            }

	            return this.receivingPendingQuantity(pkg) > 0
	                ? 'bg-amber-50 text-amber-700 border-amber-200'
	                : 'bg-slate-50 text-slate-600 border-slate-200';
	        },

	        receivingPackageStatusTextClass(pkg) {
	            if (this.receivingPackageIsReceived(pkg)) {
	                return 'text-emerald-700';
	            }

	            return this.receivingPendingQuantity(pkg) > 0 ? 'text-amber-700' : 'text-slate-600';
	        },

	        receivingConditionLabel(status) {
	            if (!status) return 'Not checked';
	            switch (String(status)) {
	                case 'damaged':
	                    return 'Damaged';
	                case 'partial':
	                    return 'Partial';
	                default:
	                    return 'OK';
	            }
	        },

	        receivingConditionClass(status) {
	            if (!status) return 'bg-slate-50 text-slate-500 border-slate-200';
	            switch (String(status)) {
	                case 'damaged':
	                    return 'bg-rose-50 text-rose-700 border-rose-200';
	                case 'partial':
	                    return 'bg-amber-50 text-amber-700 border-amber-200';
	                default:
	                    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
	            }
	        },

	        receivingConditionTextClass(status) {
	            if (!status) return 'text-slate-500';
	            switch (String(status)) {
	                case 'damaged':
	                    return 'text-rose-700';
	                case 'partial':
	                    return 'text-amber-700';
	                default:
	                    return 'text-emerald-700';
	            }
	        },

	        receivingDeliverySummary(pkg) {
	            const town = pkg?.delivery_town || '';
	            const recipient = pkg?.delivery_recipient_name || '';
	            if (recipient && town) return `${recipient} - ${town}`;
	            return recipient || town || 'No delivery details';
	        },

        receivingSharedDestinationSummary() {
            const recipient = this.shipment?.delivery_recipient_name || '';
            const phone = this.shipment?.delivery_recipient_phone || '';
            const location = this.deliveryLocationSummary();
            const parts = [recipient, phone, location && location !== '-' ? location : ''].filter(Boolean);

            return parts.length ? parts.join(' - ') : 'No shared destination set';
        },

        openReceivingSharedDestinationModal() {
            const pkg = (this.receiving.packages || [])[0];
            if (!pkg) {
                window.showToast?.('Add a package before setting the shared destination.', 'error');
                return;
            }

            this.sharedDestinationModal = {
                open: true,
                packageId: pkg.shipment_item_id,
                pkg: this.buildSharedDestinationFormPackage(pkg),
                saving: false,
            };
        },

        buildSharedDestinationFormPackage(pkg) {
            const clone = this.cloneReceivingPackage(pkg);
            const regionId = this.shipment?.delivery_region_id ? String(this.shipment.delivery_region_id) : '';
            const districtId = this.shipment?.delivery_district_id ? String(this.shipment.delivery_district_id) : '';
            const town = this.shipment?.delivery_town || clone.delivery_town || '';
            const regionName = this.shipment?.delivery_region?.name || '';
            const districtName = this.shipment?.delivery_district?.name || '';
            const isLinked = Boolean(town && regionId && districtId);

            clone.delivery_recipient_name = this.shipment?.delivery_recipient_name || clone.delivery_recipient_name || '';
            clone.delivery_recipient_phone = this.shipment?.delivery_recipient_phone || clone.delivery_recipient_phone || '';
            clone.delivery_region_id = regionId || clone.delivery_region_id || '';
            clone.delivery_district_id = districtId || clone.delivery_district_id || '';
            clone.delivery_town = town;
            clone.delivery_landmark = this.shipment?.delivery_landmark || clone.delivery_landmark || '';
            clone.delivery_instructions = this.shipment?.delivery_instructions || clone.delivery_instructions || '';
            clone._town_query = this.receivingTownDisplay(town, districtName, regionName, isLinked);
            clone._town_results = [];
            clone._town_open = false;
            clone._town_loading = false;
            clone._town_request = 0;
            clone._town_debounce = null;
            clone._town_linked = isLinked;
            clone._town_context = isLinked ? this.receivingTownContext(districtName, regionName) : '';
            clone._town_selected_display = isLinked ? clone._town_query : null;

            return clone;
        },

        closeSharedDestinationModal() {
            if (this.sharedDestinationModal.saving) return;

            const pkg = this.sharedDestinationModal.pkg;
            if (pkg?._town_debounce) {
                clearTimeout(pkg._town_debounce);
            }

            this.sharedDestinationModal = {
                open: false,
                packageId: null,
                pkg: null,
                saving: false,
            };
        },

        async saveSharedDestinationDetails() {
            const modal = this.sharedDestinationModal;
            if (!modal.pkg || modal.saving) return;

            modal.saving = true;
            const url = this.config.receivingDetailsSaveEndpoint.replace('__ITEM__', modal.pkg.shipment_item_id);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.receivingDetailsPayload(modal.pkg)),
                });
                const result = await response.json();

                if (result.success) {
                    this.applyReceivingResponse(result);
                    window.showToast?.(result.message || 'Shared destination saved.', 'success');
                    this.sharedDestinationModal = {
                        open: false,
                        packageId: null,
                        pkg: null,
                        saving: false,
                    };
                    return;
                }

                window.showToast?.(result.message || 'Failed to save shared destination.', 'error');
            } catch (e) {
                window.showToast?.('Error saving shared destination.', 'error');
            }

            modal.saving = false;
        },

        async handleReceivingDestinationModeChange(event) {
            const nextMode = event?.target?.value;
            const currentMode = this.shipmentDestinationMode();

            if (!nextMode || nextMode === currentMode) {
                if (event?.target) event.target.value = currentMode;
                return;
            }

            await this.switchReceivingDestinationMode(nextMode);

            if (event?.target) {
                event.target.value = this.shipmentDestinationMode();
            }
        },

        async switchReceivingDestinationMode(newMode) {
            const oldMode = this.shipmentDestinationMode();
            if (!this.config.saveUrl || this.receiving.dropOffSaving || newMode === oldMode) {
                return;
            }

            const message = newMode === 'per_item'
                ? 'Switch to Multiple Drop-offs? Shared destination details will be copied into packages that do not already have package-level delivery details.'
                : 'Switch to One Drop-off? Package-level destinations will be replaced by one shared destination for this shipment.';

            if (!confirm(message)) {
                return;
            }

            this.receiving.dropOffSaving = true;

            try {
                const response = await fetch(this.config.saveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ destination_mode: newMode }),
                });
                const result = await response.json();

                if (result.success) {
                    this.applyReceivingMeta(result.data || {});
                    await this.loadReceiving();
                    window.showToast?.('Drop-off type updated.', 'success');
                } else {
                    window.showToast?.(result.message || 'Failed to update drop-off type.', 'error');
                }
            } catch (e) {
                window.showToast?.('Error updating drop-off type.', 'error');
            }

            this.receiving.dropOffSaving = false;
        },

	        receivingPhotoCount(pkg, key) {
	            return Array.isArray(pkg?.[key]) ? pkg[key].length : 0;
	        },

        receivingTotalPhotoCount(pkg) {
            return this.receivingPhotoCount(pkg, 'vendor_photos')
                + this.receivingPhotoCount(pkg, 'driver_photos')
                + this.receivingPhotoCount(pkg, 'photos');
        },

        receivingDeliveryFeeLabel(pkg) {
            const fee = pkg?.delivery_fee || {};
            const amount = Number(fee.amount || fee.outstanding_amount || fee.paid_amount || 0);
            const currency = fee.currency || 'GHS';

            switch (fee.status || fee.mode || 'none') {
                case 'paid':
                    return `Paid ${currency} ${amount.toFixed(2)}`;
                case 'collect':
                    return `Collect ${currency} ${amount.toFixed(2)}`;
                case 'partially_paid':
                    return `Collect balance ${currency} ${Number(fee.outstanding_amount || 0).toFixed(2)}`;
                case 'waived':
                    return 'Fee waived';
                default:
                    return 'No delivery fee';
            }
        },

        receivingDeliveryFeeClass(pkg) {
            const status = pkg?.delivery_fee?.status || 'none';
            if (status === 'paid') return 'text-emerald-700';
            if (status === 'collect' || status === 'partially_paid') return 'text-amber-700';
            if (status === 'waived') return 'text-slate-500';
            return 'text-slate-400';
        },

        packageCustodySummary(pkg) {
            const custody = pkg?.custody || {};
            const total = Number(custody.total_labels || 0);
            const claimed = Number(custody.claimed_labels || 0);
            const delivered = Number(custody.delivered_labels || 0);
            const warehouse = Number(custody.warehouse_labels || 0);
            const drivers = Array.isArray(custody.drivers) ? custody.drivers : [];

            if (total === 0) return 'No labels';
            if (delivered === total) return 'Delivered';
            if (claimed === 0 && warehouse > 0) return 'At Warehouse';
            if (drivers.length === 1 && claimed === total) return drivers[0].name || 'Driver assigned';
            if (drivers.length > 1) return 'Multiple Drivers';
            return 'Mixed Custody';
        },

        packageCustodyClass(pkg) {
            const custody = pkg?.custody || {};
            const total = Number(custody.total_labels || 0);
            const claimed = Number(custody.claimed_labels || 0);
            const delivered = Number(custody.delivered_labels || 0);
            const warehouse = Number(custody.warehouse_labels || 0);
            const drivers = Array.isArray(custody.drivers) ? custody.drivers : [];

            if (total === 0) return 'text-slate-400';
            if (delivered === total) return 'text-blue-700';
            if (claimed === 0 && warehouse > 0) return 'text-slate-600';
            if (drivers.length === 1 && claimed === total) return 'text-emerald-700';
            return 'text-amber-700';
        },

        packageCustodyDetail(pkg) {
            return this.packageCustodyDetailLines(pkg).join(', ');
        },

        packageCustodyDetailLines(pkg) {
            const custody = pkg?.custody || {};
            const total = Number(custody.total_labels || 0);
            const claimed = Number(custody.claimed_labels || 0);
            const delivered = Number(custody.delivered_labels || 0);
            const warehouse = Number(custody.warehouse_labels || 0);

            if (total === 0) return ['Print labels first'];

            return [
                `${claimed} claimed`,
                `${warehouse} warehouse`,
                `${delivered} delivered`,
            ];
        },

        packageCustodyCanOpen(pkg) {
            return Number(pkg?.custody?.total_labels || 0) > 0;
        },

        openPackageCustodyModal(pkg) {
            if (!this.packageCustodyCanOpen(pkg)) return;
            this.packageCustodyModal = {
                open: true,
                pkg,
                packageLabel: pkg.description || pkg.tracking_code || 'Package custody',
            };
        },

        closePackageCustodyModal() {
            this.packageCustodyModal = { open: false, pkg: null, packageLabel: '' };
        },

        receivingPackageIndex(packageId) {
            return this.receiving.packages.findIndex((candidate) => Number(candidate.shipment_item_id) === Number(packageId));
        },

	        prepareReceivingPackage(pkg) {
            const fee = pkg.delivery_fee || {};
	            const prepared = {
	                ...pkg,
                vendor_photos: (Array.isArray(pkg.vendor_photos) ? pkg.vendor_photos : []).map((photo) => {
                    if (typeof photo === 'string') {
                        return { id: null, url: photo, original_name: null, recipient_phone: null };
                    }

                    return {
                        id: photo.id ?? null,
                        url: photo.url || '',
                        original_name: photo.original_name || null,
                        recipient_phone: photo.recipient_phone || null,
                    };
                }),
                driver_photos: Array.isArray(pkg.driver_photos) ? pkg.driver_photos : [],
                photos: Array.isArray(pkg.photos) ? pkg.photos : [],
                delivery_region_id: pkg.delivery_region_id ? String(pkg.delivery_region_id) : '',
                delivery_district_id: pkg.delivery_district_id ? String(pkg.delivery_district_id) : '',
	                delivery_town: pkg.delivery_town || '',
                delivery_fee: {
                    mode: fee.mode || 'none',
                    status: fee.status || 'none',
                    amount: fee.amount ?? '',
                    currency: fee.currency || 'GHS',
                    paid_amount: Number(fee.paid_amount || 0),
                    outstanding_amount: Number(fee.outstanding_amount || 0),
                    notes: fee.notes || '',
                    payment_method: fee.payment_method || 'cash',
                    payment_reference: fee.payment_reference || '',
                    paid_at: fee.paid_at || null,
	                },
		                can_split: !!pkg.can_split,
		                split_lock_reason: pkg.split_lock_reason || '',
                can_delete: !!pkg.can_delete,
                delete_lock_reason: pkg.delete_lock_reason || '',
                _receipt_photo_files: [],
	            };

	            prepared.expected_quantity = this.receivingExpectedQuantity(prepared);
	            prepared.received_quantity = Number.isFinite(Number(prepared.received_quantity)) ? Number(prepared.received_quantity) : 0;
	            prepared.damaged_quantity = Number.isFinite(Number(prepared.damaged_quantity)) ? Number(prepared.damaged_quantity) : 0;

            prepared.discrepancy_type = pkg.discrepancy_type || this.receivingDiscrepancyType(prepared);
            prepared.discrepancy_label = this.receivingDiscrepancyLabel(prepared.discrepancy_type);

            const isLinked = Boolean(prepared.delivery_town && prepared.delivery_region_id && prepared.delivery_district_id);
            prepared._town_query = this.receivingTownDisplay(
                prepared.delivery_town,
                pkg.delivery_district_name,
                pkg.delivery_region_name,
                isLinked
            );
            prepared._town_results = [];
            prepared._town_open = false;
            prepared._town_loading = false;
            prepared._town_request = 0;
            prepared._town_debounce = null;
            prepared._town_linked = isLinked;
            prepared._town_context = isLinked
                ? this.receivingTownContext(pkg.delivery_district_name, pkg.delivery_region_name)
                : '';
            prepared._town_selected_display = isLinked ? prepared._town_query : null;

            return prepared;
        },

        applyReceivingMeta(data = {}) {
            if (Object.prototype.hasOwnProperty.call(data, 'can_auto_group')) {
                this.receiving.canAutoGroup = !!data.can_auto_group;
            }

            if (Object.prototype.hasOwnProperty.call(data, 'auto_group_lock_reason')) {
                this.receiving.autoGroupLockReason = data.auto_group_lock_reason || '';
            }

            if (Object.prototype.hasOwnProperty.call(data, 'receipt')) {
                this.receiving.receipt = data.receipt || null;
            }

            if (Object.prototype.hasOwnProperty.call(data, 'destination_mode') && data.destination_mode) {
                this.shipment.destination_mode = data.destination_mode;
            }

            if (data.delivery && typeof data.delivery === 'object') {
                this.shipment.delivery_recipient_name = data.delivery.recipient_name || null;
                this.shipment.delivery_recipient_phone = data.delivery.recipient_phone || null;
                this.shipment.delivery_region_id = data.delivery.region_id || null;
                this.shipment.delivery_district_id = data.delivery.district_id || null;
                this.shipment.delivery_town = data.delivery.town || null;
                this.shipment.delivery_landmark = data.delivery.landmark || null;
                this.shipment.delivery_instructions = data.delivery.instructions || null;
                this.shipment.delivery_preference = data.delivery.delivery_preference || null;
                this.shipment.fulfillment_type = data.delivery.fulfillment_type || null;
                this.shipment.delivery_region = data.delivery.region_id
                    ? { id: data.delivery.region_id, name: data.delivery.region_name || '' }
                    : null;
                this.shipment.delivery_district = data.delivery.district_id
                    ? { id: data.delivery.district_id, name: data.delivery.district_name || '' }
                    : null;
            }

            if (Object.prototype.hasOwnProperty.call(data, 'delivery_recipient_phone')) {
                this.shipment.delivery_recipient_phone = data.delivery_recipient_phone || null;
            }
        },

        replaceReceivingPackage(pkg) {
            const prepared = this.prepareReceivingPackage(pkg);
            const index = this.receiving.packages.findIndex((candidate) => Number(candidate.shipment_item_id) === Number(prepared.shipment_item_id));

            if (index >= 0) {
                this.receiving.packages.splice(index, 1, prepared);
            } else {
                this.receiving.packages.push(prepared);
            }

            this.sortReceivingPackagesNewestFirst();

            return prepared;
        },

        sortReceivingPackagesNewestFirst() {
            this.receiving.packages = (this.receiving.packages || []).slice().sort((a, b) => {
                return Number(b?.shipment_item_id || 0) - Number(a?.shipment_item_id || 0);
            });
        },

	        applyReceivingResponse(result, pkg = null) {
	            this.applyReceivingMeta(result.data || {});

            if (Array.isArray(result.data?.receiving_packages)) {
                this.receiving.packages = result.data.receiving_packages.map((candidate) => this.prepareReceivingPackage(candidate));
                this.sortReceivingPackagesNewestFirst();
                return;
            }

	            if (Array.isArray(result.data?.packages)) {
	                this.receiving.packages = result.data.packages.map((candidate) => this.prepareReceivingPackage(candidate));
                this.sortReceivingPackagesNewestFirst();
	                return;
	            }

            if (result.data?.receiving_package) {
                this.replaceReceivingPackage(result.data.receiving_package);
            }

            if (result.data?.source_receiving_package) {
                this.replaceReceivingPackage(result.data.source_receiving_package);
            }

		            if (result.data?.package) {
                if (!Object.prototype.hasOwnProperty.call(result.data.package, 'shipment_item_id')) {
                    return;
                }

		                const prepared = this.prepareReceivingPackage(result.data.package);
		                if (pkg) {
		                    Object.assign(pkg, prepared);
		                }
		                this.replaceReceivingPackage(prepared);
	            }
	        },

	        receivingDetailsPayload(pkg) {
            const fee = pkg.delivery_fee || {};
	            return {
	                description: pkg.description || null,
	                delivery_recipient_name: pkg.delivery_recipient_name || null,
	                delivery_recipient_phone: pkg.delivery_recipient_phone || null,
	                delivery_region_id: pkg.delivery_region_id || null,
	                delivery_district_id: pkg.delivery_district_id || null,
	                delivery_town: pkg.delivery_town || null,
	                delivery_landmark: pkg.delivery_landmark || null,
	                delivery_instructions: pkg.delivery_instructions || null,
	                delivery_method: pkg.delivery_method || 'direct',
                delivery_fee_mode: fee.mode || 'none',
                delivery_fee_amount: fee.mode && fee.mode !== 'none' && fee.amount !== '' && fee.amount !== null
                    ? Number(fee.amount)
                    : null,
                delivery_fee_notes: fee.notes || null,
                delivery_fee_payment_method: fee.payment_method || 'cash',
                delivery_fee_payment_reference: fee.payment_reference || null,
	            };
	        },

	        receivingReceivePayload(pkg) {
	            return {
	                ...this.receivingDetailsPayload(pkg),
	                received_quantity: Number.isFinite(Number(pkg.received_quantity)) ? Number(pkg.received_quantity) : 0,
	                damaged_quantity: Number.isFinite(Number(pkg.damaged_quantity)) ? Number(pkg.damaged_quantity) : 0,
	                condition_status: pkg.condition_status || 'ok',
	                notes: pkg.notes || null,
	            };
	        },

        receivingRequestOptions(pkg) {
            const payload = this.receivingReceivePayload(pkg);
            const files = Array.isArray(pkg._receipt_photo_files) ? pkg._receipt_photo_files : [];
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            if (!files.length) {
                return {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                };
            }

            const formData = new FormData();
            Object.entries(payload).forEach(([key, value]) => {
                if (value === null || value === undefined) {
                    formData.append(key, '');
                    return;
                }

                formData.append(key, String(value));
            });
            files.forEach((file) => formData.append('photos[]', file));

            return {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: formData,
            };
        },

	        cloneReceivingPackage(pkg) {
	            const clone = JSON.parse(JSON.stringify(pkg || {}));
	            return this.prepareReceivingPackage(clone);
	        },

	        openReceivingPackageModal(pkg, step = 1) {
	            this.receivingPackageModal = {
	                open: true,
	                step: this.isPerItemMode() ? step : 1,
	                packageId: pkg.shipment_item_id,
	                packageLabel: pkg.description || pkg.tracking_code || 'Package details',
	                pkg: this.cloneReceivingPackage(pkg),
	                savingDetails: false,
	                savingReceive: false,
	            };
            this.receivingPackageModal.pkg.condition_status ||= 'ok';
	        },

	        closeReceivingPackageModal() {
	            if (this.receivingPackageModal.savingDetails || this.receivingPackageModal.savingReceive) return;

	            this.receivingPackageModal = {
	                open: false,
	                step: 1,
	                packageId: null,
	                packageLabel: '',
	                pkg: null,
	                savingDetails: false,
	                savingReceive: false,
	            };
	        },

	        setReceivingPackageModalStep(step) {
	            this.receivingPackageModal.step = step;
	        },

        setReceivingReceiptPhotos(pkg, files) {
            pkg._receipt_photo_files = Array.from(files || []);
        },

        receivingReceiptPhotoNames(pkg) {
            const files = Array.isArray(pkg?._receipt_photo_files) ? pkg._receipt_photo_files : [];
            if (!files.length) return '';
            if (files.length === 1) return files[0].name;
            return `${files.length} receipt photos selected`;
        },

        openReceivingPhotosModal(pkg) {
            this.receivingPhotosModal = {
                open: true,
                packageId: pkg.shipment_item_id,
                packageLabel: pkg.description || pkg.tracking_code || 'Package photos',
                pkg: this.cloneReceivingPackage(pkg),
                files: [],
                uploading: false,
            };
        },

        closeReceivingPhotosModal() {
            if (this.receivingPhotosModal.uploading) return;

            this.receivingPhotosModal = {
                open: false,
                packageId: null,
                packageLabel: '',
                pkg: null,
                files: [],
                uploading: false,
            };
        },

        setReceivingPhotosModalFiles(files) {
            this.receivingPhotosModal.files = Array.from(files || []);
        },

        syncReceivingPhotosModalFromTable() {
            if (!this.receivingPhotosModal.open || !this.receivingPhotosModal.packageId) return;
            const current = this.receiving.packages.find((candidate) => Number(candidate.shipment_item_id) === Number(this.receivingPhotosModal.packageId));
            if (current) {
                this.receivingPhotosModal.pkg = this.cloneReceivingPackage(current);
            }
        },

        async uploadReceiptPhotosFromModal() {
            const modal = this.receivingPhotosModal;
            if (!modal.pkg || modal.uploading || !modal.files.length) return;

            if (!this.receivingPackageIsReceived(modal.pkg)) {
                modal.pkg._receipt_photo_files = modal.files;
                this.closeReceivingPhotosModal();
                this.receivingPackageModal = {
                    open: true,
                    step: 1,
                    packageId: modal.pkg.shipment_item_id,
                    packageLabel: modal.pkg.description || modal.pkg.tracking_code || 'Package details',
                    pkg: modal.pkg,
                    savingDetails: false,
                    savingReceive: false,
                };
                this.receivingPackageModal.pkg.condition_status ||= 'ok';
                window.showToast?.('Confirm the intake details, then Save and Receive to attach the receipt photos.', 'info');
                return;
            }

            modal.uploading = true;
            modal.pkg._receipt_photo_files = modal.files;
            const url = this.config.receiveSaveEndpoint.replace('__ITEM__', modal.pkg.shipment_item_id);

            try {
                const response = await fetch(url, this.receivingRequestOptions(modal.pkg));
                const result = await response.json();
                if (result.success) {
                    this.applyReceivingResponse(result);
                    this.syncReceivingPhotosModalFromTable();
                    modal.files = [];
                    window.showToast?.('Receipt photos uploaded.', 'success');
                } else {
                    window.showToast?.(result.message || 'Failed to upload receipt photos.', 'error');
                }
            } catch (e) {
                window.showToast?.('Error uploading receipt photos.', 'error');
            }

            modal.uploading = false;
        },

	        syncReceivingPackageModalFromTable() {
	            if (!this.receivingPackageModal.open || !this.receivingPackageModal.packageId) return;

	            const current = this.receiving.packages.find((candidate) => Number(candidate.shipment_item_id) === Number(this.receivingPackageModal.packageId));
	            if (current) {
	                this.receivingPackageModal.pkg = this.cloneReceivingPackage(current);
	            }
	        },

        closeReceivingTownSearch(pkg) {
            pkg._town_open = false;
        },

        clearReceivingTown(pkg) {
            clearTimeout(pkg._town_debounce);
            pkg._town_query = '';
            pkg._town_results = [];
            pkg._town_open = false;
            pkg._town_loading = false;
            pkg._town_linked = false;
            pkg._town_context = '';
            pkg._town_selected_display = null;
            pkg.delivery_town = '';
            pkg.delivery_region_id = '';
            pkg.delivery_district_id = '';
        },

        updateReceivingTownQuery(pkg, value) {
            pkg._town_query = value;
            pkg.delivery_town = value.trim();
            pkg.delivery_region_id = '';
            pkg.delivery_district_id = '';
            pkg._town_linked = false;
            pkg._town_context = '';
            pkg._town_selected_display = null;
            this.searchReceivingTownOptions(pkg);
        },

        async searchReceivingTownOptions(pkg) {
            const query = (pkg._town_query || '').trim();
            clearTimeout(pkg._town_debounce);

            if (query.length < 2) {
                pkg._town_results = [];
                pkg._town_open = false;
                pkg._town_loading = false;
                return;
            }

            const requestId = ++pkg._town_request;
            pkg._town_debounce = setTimeout(async () => {
                pkg._town_loading = true;
                try {
                    const url = new URL(this.config.townsSearchUrl, window.location.origin);
                    url.searchParams.set('search', query);
                    url.searchParams.set('active', '1');
                    url.searchParams.set('limit', '12');

                    const response = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const result = await response.json();
                    if (requestId !== pkg._town_request) return;

                    pkg._town_results = (result.data?.towns || []).map(town => ({
                        ...town,
                        display: this.receivingTownDisplay(town.name, town.district_name, town.region_name, true),
                        context: this.receivingTownContext(town.district_name, town.region_name),
                    }));
                    pkg._town_open = pkg._town_results.length > 0;
                } catch (e) {
                    if (requestId === pkg._town_request) {
                        pkg._town_results = [];
                        pkg._town_open = false;
                    }
                } finally {
                    if (requestId === pkg._town_request) {
                        pkg._town_loading = false;
                    }
                }
            }, 300);
        },

        selectReceivingTownOption(pkg, town) {
            const display = this.receivingTownDisplay(town.name, town.district_name, town.region_name, true);
            pkg.delivery_town = town.name || '';
            pkg.delivery_region_id = town.region_id ? String(town.region_id) : '';
            pkg.delivery_district_id = town.district_id ? String(town.district_id) : '';
            pkg._town_query = pkg === this.receivingAddPackageModal ? (town.name || '') : display;
            pkg._town_results = [];
            pkg._town_open = false;
            pkg._town_loading = false;
            pkg._town_linked = Boolean(town.region_id && town.district_id);
            pkg._town_context = this.receivingTownContext(town.district_name, town.region_name);
            pkg._town_selected_display = pkg === this.receivingAddPackageModal ? (town.name || '') : display;
        },

	        async loadReceiving() {
	            this.receiving.loading = true;
	            this.receivingLoaded = true;
	            try {
                const response = await fetch(this.config.receivingDataEndpoint, {
                    headers: { 'Accept': 'application/json' },
                });
                const result = await response.json();
                if (result.success) {
                    this.applyReceivingMeta(result.data || {});
                    this.receiving.packages = (result.data.packages || []).map(pkg => this.prepareReceivingPackage(pkg));
                    this.sortReceivingPackagesNewestFirst();
                    this.receiving.canReceive = result.data.can_receive;
                    this.receiving.receipt = result.data.receipt;
                    this.receiving.assignmentId = result.data.assignment_id;
                    this.receiving.canAutoGroup = !!result.data.can_auto_group;
                    this.receiving.autoGroupLockReason = result.data.auto_group_lock_reason || '';
                }
	            } catch (e) { console.error('Failed to load receiving data', e); }
	            this.receiving.loading = false;
	        },

        openReceivingAddPackageModal() {
            const draft = {
                open: true,
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
                delivery_fee: {
                    mode: 'none',
                    status: 'none',
                    amount: '',
                    currency: 'GHS',
                    notes: '',
                    payment_method: 'cash',
                    payment_reference: '',
                },
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
                saving: false,
            };

            this.receivingAddPackageModal = draft;
        },

        closeReceivingAddPackageModal() {
            if (this.receivingAddPackageModal.saving) return;

            if (this.receivingAddPackageModal._town_debounce) {
                clearTimeout(this.receivingAddPackageModal._town_debounce);
            }

            this.receivingAddPackageModal = { open: false, saving: false };
        },

        async addReceivingPackage() {
            const modal = this.receivingAddPackageModal;
            if (modal.saving || !this.config.addPackageUrl) return;

            if (!String(modal.description || '').trim()) {
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
                const files = Array.isArray(modal._receipt_photo_files) ? modal._receipt_photo_files : [];
                const payload = {
                    ...(this.isPerItemMode() ? this.receivingDetailsPayload(modal) : {}),
                    description: modal.description || null,
                    quantity: Number(modal.quantity),
                };
                let body;
                let headers = {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                };

                if (files.length) {
                    body = new FormData();
                    Object.entries(payload).forEach(([key, value]) => {
                        body.append(key, value === null || value === undefined ? '' : String(value));
                    });
                    files.forEach((file) => body.append('photos[]', file));
                } else {
                    headers = {
                        ...headers,
                        'Content-Type': 'application/json',
                    };
                    body = JSON.stringify(payload);
                }

                const response = await fetch(this.config.addPackageUrl, {
                    method: 'POST',
                    headers,
                    body,
                });
                const result = await response.json();

                if (result.success) {
                    this.applyReceivingMeta(result.data || {});
                    const receivingPackage = result.data?.receiving_package;
                    if (receivingPackage) {
                        this.replaceReceivingPackage(receivingPackage);
                    } else {
                        await this.loadReceiving();
                    }
                    window.showToast?.(result.message || 'Package added.', 'success');
                    modal.saving = false;
                    this.closeReceivingAddPackageModal();
                } else {
                    window.showToast?.(result.message || 'Failed to add package.', 'error');
                }
            } catch (e) {
                window.showToast?.('Error adding package.', 'error');
            }

            modal.saving = false;
        },

        async removeReceivingPackage(pkg) {
            if (!pkg?.shipment_item_id || !this.config.deletePackageUrlTemplate) return;

            if (!pkg.can_delete) {
                window.showToast?.(pkg.delete_lock_reason || 'This package can no longer be removed.', 'error');
                return;
            }

            const label = pkg.description || pkg.tracking_code || 'this package';
            const hasWarehouseReceipt = this.receivingPackageIsReceived(pkg)
                || Number(pkg?.damaged_quantity ?? 0) > 0
                || (Array.isArray(pkg?.photos) && pkg.photos.length > 0);
            const prompt = hasWarehouseReceipt
                ? `Remove ${label}? This will undo warehouse receiving for this package, delete its receipt photos, then remove the package.`
                : `Remove ${label}? Vendor photos and empty receiving records for this package will be deleted.`;

            this.receivingRemoveConfirm = {
                open: true,
                pkg,
                title: `Remove ${label}?`,
                message: prompt.replace(`Remove ${label}? `, ''),
                loading: false,
            };
        },

        closeReceivingRemoveConfirm() {
            if (this.receivingRemoveConfirm.loading) return;

            this.receivingRemoveConfirm = { open: false, pkg: null, title: '', message: '', loading: false };
        },

        async confirmRemoveReceivingPackage() {
            const pkg = this.receivingRemoveConfirm.pkg;
            if (!pkg?.shipment_item_id || !this.config.deletePackageUrlTemplate || this.receivingRemoveConfirm.loading) return;

            this.receivingRemoveConfirm.loading = true;
            const url = this.config.deletePackageUrlTemplate.replace('__PKG__', pkg.shipment_item_id);
            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const result = await response.json();

                if (result.success) {
                    const deletedId = Number(result.data?.deleted_package_id || pkg.shipment_item_id);
                    if (Array.isArray(result.data?.receiving_packages)) {
                        this.applyReceivingResponse(result);
                    } else {
                        this.applyReceivingMeta(result.data || {});
                        this.receiving.packages = this.receiving.packages.filter((candidate) => Number(candidate.shipment_item_id) !== deletedId);
                    }

                    if (Number(this.receivingPackageModal.packageId) === deletedId) {
                        this.closeReceivingPackageModal();
                    }
                    if (Number(this.receivingPhotosModal.packageId) === deletedId) {
                        this.closeReceivingPhotosModal();
                    }
                    if (Number(this.receivingSplitModal.packageId) === deletedId) {
                        this.closeReceivingSplitModal();
                    }
                    if (Number(this.receivingLabelPrintModal.pkg?.shipment_item_id) === deletedId) {
                        this.closeReceivingLabelPrintModal();
                    }

                    this.receivingRemoveConfirm = { open: false, pkg: null, title: '', message: '', loading: false };
                    window.showToast?.(result.message || 'Package removed.', 'success');
                } else {
                    this.applyReceivingMeta(result.data || {});
                    window.showToast?.(result.message || 'Failed to remove package.', 'error');
                    this.receivingRemoveConfirm.loading = false;
                }
            } catch (e) {
                window.showToast?.('Error removing package.', 'error');
                this.receivingRemoveConfirm.loading = false;
            }
        },

	        async autoGroupReceivingPackagesByPhone() {
	            if (this.receiving.autoGrouping || !this.config.autoGroupByPhoneEndpoint) {
	                return;
	            }

            if (!this.receiving.canAutoGroup && this.receiving.autoGroupLockReason) {
                window.showToast?.(this.receiving.autoGroupLockReason, 'error');
                return;
            }

            this.receiving.autoGrouping = true;

            try {
                const response = await fetch(this.config.autoGroupByPhoneEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });
                const result = await response.json();

                if (result.success) {
                    this.applyReceivingResponse(result);
                    if (!Array.isArray(result.data?.receiving_packages)) {
                        await this.loadReceiving();
                    }
                    window.showToast?.(result.message || 'Packages grouped by phone.', 'success');
                } else {
                    this.applyReceivingMeta(result.data || {});
                    window.showToast?.(result.message || 'Auto-group failed.', 'error');
                }
            } catch (e) {
                window.showToast?.('Error grouping packages by phone.', 'error');
            }

            this.receiving.autoGrouping = false;
        },

        async saveReceivingPackageDetails(pkg) {
            this.receiving.detailsSaving = true;
            const url = this.config.receivingDetailsSaveEndpoint.replace('__ITEM__', pkg.shipment_item_id);
            try {
                const response = await fetch(url, {
                    method: 'POST',
	                    headers: {
	                        'Content-Type': 'application/json',
	                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
	                        'Accept': 'application/json',
	                    },
	                    body: JSON.stringify(this.receivingDetailsPayload(pkg)),
	                });
                const result = await response.json();
                if (result.success) {
                    this.applyReceivingResponse(result, pkg);
                    window.showToast?.(result.message || 'Package details saved.', 'success');
                } else {
                    window.showToast?.(result.message || 'Failed to save package details.', 'error');
                }
            } catch (e) {
                window.showToast?.('Error saving package details.', 'error');
            }
	            this.receiving.detailsSaving = false;
	        },

	        async saveReceivingPackageModalDetails() {
	            const modal = this.receivingPackageModal;
	            if (!modal.pkg || modal.savingDetails || modal.savingReceive) return;

	            modal.savingDetails = true;
	            const url = this.config.receivingDetailsSaveEndpoint.replace('__ITEM__', modal.pkg.shipment_item_id);
	            try {
	                const response = await fetch(url, {
	                    method: 'POST',
	                    headers: {
	                        'Content-Type': 'application/json',
	                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
	                        'Accept': 'application/json',
	                    },
	                    body: JSON.stringify(this.receivingDetailsPayload(modal.pkg)),
	                });
	                const result = await response.json();
	                if (result.success) {
	                    this.applyReceivingResponse(result);
	                    this.syncReceivingPackageModalFromTable();
	                    window.showToast?.(result.message || 'Package details saved.', 'success');
	                    modal.savingDetails = false;
	                    this.closeReceivingPackageModal();
	                } else {
	                    window.showToast?.(result.message || 'Failed to save package details.', 'error');
	                }
	            } catch (e) {
	                window.showToast?.('Error saving package details.', 'error');
	            }
	            modal.savingDetails = false;
	        },

        openReceivingSplitModal(pkg) {
            if (!pkg.can_split) {
                window.showToast?.(pkg.split_lock_reason || 'This package can no longer be split.', 'error');
                return;
            }

            const photos = (pkg.vendor_photos || []).filter((photo) => photo && photo.id);
            if (!photos.length) {
                window.showToast?.('There are no vendor photos available to split on this package.', 'error');
                return;
            }

            const packageIndex = this.receiving.packages.findIndex((candidate) => Number(candidate.shipment_item_id) === Number(pkg.shipment_item_id));
            this.receivingSplitModal = {
                open: true,
                packageId: pkg.shipment_item_id,
                packageLabel: packageIndex >= 0 ? `Package ${packageIndex + 1}` : 'Package',
                photos,
                selectedIds: [],
                saving: false,
            };
        },

        closeReceivingSplitModal() {
            if (this.receivingSplitModal.saving) return;

            this.receivingSplitModal = {
                open: false,
                packageId: null,
                packageLabel: '',
                photos: [],
                selectedIds: [],
                saving: false,
            };
        },

        toggleReceivingSplitPhoto(id) {
            const idx = this.receivingSplitModal.selectedIds.indexOf(id);
            if (idx >= 0) this.receivingSplitModal.selectedIds.splice(idx, 1);
            else this.receivingSplitModal.selectedIds.push(id);
        },

        async executeReceivingSplit() {
            if (!this.receivingSplitModal.packageId || !this.receivingSplitModal.selectedIds.length || this.receivingSplitModal.saving) {
                return;
            }

            this.receivingSplitModal.saving = true;
            const url = this.config.splitPackageUrlTemplate.replace('__PKG__', this.receivingSplitModal.packageId);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ photo_ids: this.receivingSplitModal.selectedIds }),
                });
                const result = await response.json();

                if (result.success) {
                    if (Array.isArray(result.data?.receiving_packages)) {
                        this.applyReceivingResponse(result);
                    } else {
                        this.applyReceivingMeta(result.data || {});
                        const sourcePackage = result.data?.source_receiving_package;
                        const newPackage = result.data?.receiving_package;
                        const sourceIndex = this.receiving.packages.findIndex((candidate) => Number(candidate.shipment_item_id) === Number(this.receivingSplitModal.packageId));

                        if (sourcePackage) {
                            this.replaceReceivingPackage(sourcePackage);
                        }

                        if (newPackage) {
                            this.replaceReceivingPackage(newPackage);
                        }
                    }

                    window.showToast?.(result.message || 'Package split successfully.', 'success');
                    this.receivingSplitModal.saving = false;
                    this.closeReceivingSplitModal();
                } else {
                    window.showToast?.(result.message || 'Failed to split package.', 'error');
                }
            } catch (e) {
                window.showToast?.('Error splitting package.', 'error');
            }

            this.receivingSplitModal.saving = false;
        },

        async receivePackage(pkg) {
            this.receiving.saving = true;
            const url = this.config.receiveSaveEndpoint.replace('__ITEM__', pkg.shipment_item_id);
            try {
                const response = await fetch(url, this.receivingRequestOptions(pkg));
                const result = await response.json();
                if (result.success) {
                    this.applyReceivingResponse(result, pkg);
                    window.showToast?.(result.message || 'Package received.', 'success');
                } else {
                    window.showToast?.(result.message || 'Failed.', 'error');
                }
            } catch (e) { window.showToast?.('Error receiving package.', 'error'); }
	            this.receiving.saving = false;
	        },

			        async receivePackageFromModal() {
		            const modal = this.receivingPackageModal;
		            if (!modal.pkg || modal.savingDetails || modal.savingReceive) return;

                    const wasFinalized = this.receivingIsFinalized();

		            modal.savingReceive = true;
		            this.receiving.saving = true;
		            const url = this.config.receiveSaveEndpoint.replace('__ITEM__', modal.pkg.shipment_item_id);
                    const receivedPackageId = modal.pkg.shipment_item_id;
		            try {
		                const response = await fetch(url, this.receivingRequestOptions(modal.pkg));
			                const result = await response.json();
			                if (result.success) {
			                    this.applyReceivingResponse(result);
			                    modal.savingReceive = false;
			                    this.receiving.saving = false;
			                    this.closeReceivingPackageModal();
                            const updatedPackage = this.receiving.packages.find((candidate) => Number(candidate.shipment_item_id) === Number(receivedPackageId));
                            if (!wasFinalized && updatedPackage && this.receivingPackageIsReceived(updatedPackage)) {
                                this.openReceivingLabelPrintModal(updatedPackage);
                            }
		                } else {
		                    window.showToast?.(result.message || 'Failed.', 'error');
		                }
	            } catch (e) {
	                window.showToast?.('Error receiving package.', 'error');
	            }
	            modal.savingReceive = false;
		            this.receiving.saving = false;
		        },

        openReceivingLabelPrintModal(pkg) {
            if (!pkg?.shipment_item_id) return;

            const receivedQuantity = Math.max(1, Number(pkg.received_quantity || 1));
            this.receivingLabelPrintModal = {
                open: true,
                pkg,
                packageLabel: pkg.description || pkg.tracking_code || 'Package',
                trackingCode: pkg.tracking_code || pkg.barcode_value || '',
                labelCount: receivedQuantity,
                printing: false,
            };
        },

        closeReceivingLabelPrintModal() {
            if (this.receivingLabelPrintModal.printing) return;

            this.receivingLabelPrintModal = {
                open: false,
                pkg: null,
                packageLabel: '',
                trackingCode: '',
                labelCount: 1,
                printing: false,
            };
        },

        async printLabelsFromReceivingModal() {
            const modal = this.receivingLabelPrintModal;
            if (!modal.pkg || modal.printing) return;

            modal.printing = true;
            const success = await this.printLabel(modal.pkg, modal.labelCount);
            modal.printing = false;

            if (success) {
                this.closeReceivingLabelPrintModal();
            }
        },

        async printLabel(pkg, labelCount = 1) {
            const url = this.config.receivePrintLabelEndpoint.replace('__ITEM__', pkg.shipment_item_id);
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ label_count: labelCount }),
                });
                const result = await response.json();
                if (result.success) {
                    this.applyReceivingResponse(result, pkg);
                    if (result.data?.label_html) {
                        const w = window.open('', '_blank', 'width=400,height=600');
                        if (w) { w.document.write(result.data.label_html); w.document.close(); setTimeout(() => w.print(), 300); }
                    }
                    window.showToast?.('Label generated.', 'success');
                    return true;
                } else {
                    window.showToast?.(result.message || 'Failed.', 'error');
                }
            } catch (e) { window.showToast?.('Error printing label.', 'error'); }
            return false;
        },

        finalizeConfirmOpen: false,

        openFinalizeConfirm() {
            if (!this.canFinalizeReceiving()) return;

            const hasDiscrepancies = this.receiving.packages.some((pkg) => pkg.discrepancy_type && pkg.discrepancy_type !== 'none');
            if (!hasDiscrepancies) {
                this.approvalReason = '';
            }
            this.finalizeConfirmOpen = true;
        },

        async finalizeReceiving() {
            const hasDiscrepancies = this.receiving.packages.some((pkg) => pkg.discrepancy_type && pkg.discrepancy_type !== 'none');

            if (hasDiscrepancies && !this.canApproveReceivingDiscrepancy) {
                window.showToast?.('Discrepancy finalization requires warehouse manager approval.', 'error');
                return;
            }

            if (hasDiscrepancies && !String(this.approvalReason || '').trim()) {
                window.showToast?.('Approval reason is required for discrepancy finalization.', 'error');
                return;
            }

            this.receiving.saving = true;
            try {
                const response = await fetch(this.config.receiveFinalizeEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        notes: String(this.finalizeNotes || '').trim() || null,
                        approval_reason: hasDiscrepancies
                            ? String(this.approvalReason || '').trim() || null
                            : null,
                    }),
                });
                const result = await response.json();
                if (result.success) {
                    this.applyReceivingMeta(result.data || {});
                    this.finalizeConfirmOpen = false;
                    window.showToast?.('Receiving finalized!', 'success');
                } else {
                    window.showToast?.(result.message || 'Failed to finalize.', 'error');
                }
            } catch (e) { window.showToast?.('Error finalizing.', 'error'); }
            this.receiving.saving = false;
        },

        init() {
            this.config = window.shipmentShowConfig;
            this.shipment = this.config.shipment;
            this.canManage = this.config.canManage;
            this.canManageCharges = this.config.canManageCharges ?? false;
            this.canApproveReceivingDiscrepancy = !!this.config.canApproveReceivingDiscrepancy;
            this.isSuperAdmin = this.config.isSuperAdmin ?? false;
            this.invoice = this.config.invoice;
            this.invoiceHistory = this.config.invoiceHistory || [];
            if (!this.invoice && this.invoiceHistory.length > 0) {
                const activeInvoice = this.invoiceHistory.find(row => !!row.is_active);
                this.invoice = activeInvoice || this.invoiceHistory[0];
            }
            this.assignment = this.config.assignment;
            this.assignmentHistory = this.config.assignmentHistory || [];

            // Honour ?tab=<name> query param so deep-links (including the
            // legacy /edit redirect) open the right tab.
            const tabParam = new URLSearchParams(window.location.search).get('tab');
            if (tabParam) {
                const normalizedTab = ['overview', 'packages', 'invoice', 'payments', 'assignment', 'tracking', 'charges', 'custody'].includes(tabParam)
                    ? 'receiving'
                    : tabParam;

                this.activeTab = normalizedTab;
                if (normalizedTab === 'receiving' && !this.receivingLoaded) {
                    this.loadReceiving();
                }
            }

            if (this.activeTab === 'receiving' && !this.receivingLoaded) {
                this.loadReceiving();
            }

            if (!this.chargesLoaded) {
                this.loadCharges(true);
            }

            this.loadItems();
        },

        async loadItems() {
            this.items.loading = true;
            try {
                const response = await fetch(this.config.itemsEndpoint);
                const data = await response.json();
                this.items.data = data.data || data;
            } catch (error) {
                console.error('Failed to load items:', error);
            } finally {
                this.items.loading = false;
            }
        },

        async loadTracking() {
            this.tracking.loading = true;
            try {
                const response = await fetch(this.config.trackingEndpoint);
                const data = await response.json();
                this.tracking.data  = data.data  || [];
                this.tracking.items = data.items || [];
            } catch (error) {
                console.error('Failed to load tracking:', error);
            } finally {
                this.tracking.loading = false;
            }
        },

        openAssignmentHistoryModal() {
            this.assignmentHistoryModalOpen = true;
        },

        async openTrackingHistoryModal() {
            this.trackingHistoryModalOpen = true;
            if (!this.tracking.data.length && !this.tracking.loading) {
                await this.loadTracking();
            }
        },

        itemsAreDivergent() {
            const batchIds = this.tracking.items.map(i => i.sort_batch?.id ?? null).filter(Boolean);
            return batchIds.length > 0 && new Set(batchIds).size > 1;
        },

        toggleItemDetails(itemId) {
            this.tracking.itemsExpanded[itemId] = !this.tracking.itemsExpanded[itemId];
        },

        isItemExpanded(itemId) {
            return !!this.tracking.itemsExpanded[itemId];
        },

        itemPipelineStages(item) {
            const isTransfer = item.sort_batch?.dispatch_mode === 'transfer';
            return [
                {
                    key: 'warehouse', label: 'At Warehouse',
                    completed: ['at_warehouse','sorted','in_transit','at_destination','out_for_delivery','delivered','returned'].includes(item.status),
                    active: item.status === 'at_warehouse',
                    failed: false,
                },
                {
                    key: 'sorted', label: 'Sorted',
                    completed: !!item.sort_batch?.sealed_at,
                    active: !!(item.sort_batch && !item.sort_batch.sealed_at),
                    failed: false,
                },
                {
                    key: 'transit', label: isTransfer ? 'In Transit' : 'Out for Delivery',
                    completed: isTransfer ? !!item.transport_manifest?.received_at : !!item.delivery_run?.completed_at,
                    active: isTransfer
                        ? !!(item.transport_manifest && !item.transport_manifest.received_at)
                        : !!(item.delivery_run && !item.delivery_run.completed_at),
                    failed: false,
                },
                {
                    key: 'delivered', label: 'Delivered',
                    completed: item.delivery_outcome?.status === 'delivered',
                    active: item.delivery_outcome?.status === 'pending' && !!item.delivery_run?.dispatched_at,
                    failed: item.delivery_outcome?.status === 'failed',
                },
            ];
        },

        itemStatusBadgeClass(status) {
            const map = {
                pending:          'bg-slate-100 text-slate-600',
                picked_up:        'bg-violet-100 text-violet-700',
                at_warehouse:     'bg-blue-100 text-blue-700',
                sorted:           'bg-indigo-100 text-indigo-700',
                in_transit:       'bg-orange-100 text-orange-700',
                at_destination:   'bg-teal-100 text-teal-700',
                out_for_delivery: 'bg-amber-100 text-amber-700',
                delivered:        'bg-emerald-100 text-emerald-700',
                returned:         'bg-rose-100 text-rose-700',
            };
            return map[status] || 'bg-slate-100 text-slate-600';
        },

        timelineEventDotClass(status) {
            const map = {
                created:               'bg-slate-400',
                submitted:             'bg-blue-400',
                invoice_sent:          'bg-sky-500',
                invoice_accepted:      'bg-cyan-500',
                invoice_rejected:      'bg-rose-500',
                invoice_cancelled:     'bg-rose-400',
                pickup_assigned:       'bg-violet-500',
                en_route:              'bg-violet-400',
                arrived:               'bg-violet-600',
                picked_up:             'bg-purple-500',
                arrived_warehouse:     'bg-indigo-400',
                at_warehouse:          'bg-indigo-500',
                sorted:                'bg-indigo-600',
                in_transit:            'bg-orange-500',
                at_destination:        'bg-teal-500',
                received_at_destination: 'bg-teal-600',
                out_for_delivery:      'bg-amber-500',
                delivered:             'bg-emerald-500',
                cancelled:             'bg-rose-500',
            };
            return map[status] || 'bg-slate-400';
        },

        timelineEventBadgeClass(status) {
            const map = {
                created:               'bg-slate-100 text-slate-700',
                submitted:             'bg-blue-100 text-blue-700',
                invoice_sent:          'bg-sky-100 text-sky-700',
                invoice_accepted:      'bg-cyan-100 text-cyan-700',
                invoice_rejected:      'bg-rose-100 text-rose-700',
                invoice_cancelled:     'bg-rose-100 text-rose-600',
                pickup_assigned:       'bg-violet-100 text-violet-700',
                en_route:              'bg-violet-100 text-violet-600',
                arrived:               'bg-violet-100 text-violet-800',
                picked_up:             'bg-purple-100 text-purple-700',
                arrived_warehouse:     'bg-indigo-100 text-indigo-600',
                at_warehouse:          'bg-indigo-100 text-indigo-700',
                sorted:                'bg-indigo-100 text-indigo-800',
                in_transit:            'bg-orange-100 text-orange-700',
                at_destination:        'bg-teal-100 text-teal-700',
                received_at_destination: 'bg-teal-100 text-teal-800',
                out_for_delivery:      'bg-amber-100 text-amber-700',
                delivered:             'bg-emerald-100 text-emerald-700',
                cancelled:             'bg-rose-100 text-rose-700',
            };
            return map[status] || 'bg-slate-100 text-slate-700';
        },

        activeStatuses() {
            return ['pending', 'sent', 'accepted'];
        },

        hasActiveInvoice() {
            return this.invoiceHistory.some(row => this.activeStatuses().includes(row.status));
        },

        canCreateInvoice() {
            // Super admins can create invoices regardless of shipment status
            if (this.isSuperAdmin) {
                return this.canManage && !this.hasActiveInvoice();
            }
            // Phase 3: Invoice can be created at 'submitted' (old flow) OR 'at_warehouse' (new flow after pickup)
            const invoiceableStatuses = ['submitted', 'at_warehouse'];
            return this.canManage && invoiceableStatuses.includes(this.shipment.status) && !this.hasActiveInvoice();
        },

        activeInvoiceBlockReason() {
            if (this.hasActiveInvoice()) {
                return 'Shipment already has an active invoice (pending, sent, or accepted).';
            }
            if (!this.isSuperAdmin) {
                const invoiceableStatuses = ['submitted', 'at_warehouse'];
                if (!invoiceableStatuses.includes(this.shipment.status)) {
                    return 'Invoice can be created when the shipment is submitted or received at warehouse.';
                }
            }
            return '';
        },

        activeInvoice() {
            return this.invoiceHistory.find(row => this.activeStatuses().includes(row.status)) || null;
        },

        openCreateInvoiceModal() {
            this.invoiceUiError = '';
            if (!this.canCreateInvoice()) {
                this.invoiceUiError = this.activeInvoiceBlockReason() || 'Cannot create invoice right now.';
                if (window.showToast && this.invoiceUiError) {
                    window.showToast(this.invoiceUiError, 'error');
                }
                return;
            }
            this.invoiceModalOpen = true;
        },

        closeCreateInvoiceModal() {
            this.invoiceModalOpen = false;
        },

        openInvoiceDetailModal(invoiceId = null) {
            const targetInvoice = invoiceId
                ? this.invoiceHistory.find(row => Number(row.id) === Number(invoiceId))
                : (this.activeInvoice() || this.invoice);

            if (!targetInvoice) {
                return;
            }

            window.open('/admin/invoices/' + targetInvoice.id, '_blank');
        },

        openActiveInvoiceModal() {
            const active = this.activeInvoice();
            if (active) {
                window.open('/admin/invoices/' + active.id, '_blank');
            }
        },

        closeInvoiceDetailModal() {
            this.invoiceDetailModalOpen = false;
        },

        setInvoiceStatusFilter(value) {
            this.invoiceTable.statusFilter = value;
            this.invoiceTable.statusFilterLabel = value ? value.charAt(0).toUpperCase() + value.slice(1) : 'All statuses';
            this.invoiceTable.page = 1;
        },

        toggleInvoiceColumn(key) {
            if (!(key in this.invoiceTable.visibleColumns)) return;
            const enabledCount = Object.values(this.invoiceTable.visibleColumns).filter(Boolean).length;
            if (this.invoiceTable.visibleColumns[key] && enabledCount <= 1) {
                return;
            }
            this.invoiceTable.visibleColumns[key] = !this.invoiceTable.visibleColumns[key];
        },

        visibleInvoiceColumnCount() {
            return Object.values(this.invoiceTable.visibleColumns).filter(Boolean).length;
        },

        filteredInvoiceRows() {
            let rows = Array.isArray(this.invoiceHistory) ? [...this.invoiceHistory] : [];

            const search = (this.invoiceTable.search || '').trim().toLowerCase();
            if (search) {
                rows = rows.filter(row => {
                    const haystacks = [
                        row.invoice_number,
                        row.status,
                        row.status_label,
                        row.notes,
                        row.vendor_notes,
                        row.rejection_reason,
                        row.cancel_reason,
                    ];
                    return haystacks.some(value => (value || '').toString().toLowerCase().includes(search));
                });
            }

            if (this.invoiceTable.statusFilter) {
                rows = rows.filter(row => row.status === this.invoiceTable.statusFilter);
            }

            const direction = this.invoiceTable.sortDirection === 'asc' ? 1 : -1;
            const sortBy = this.invoiceTable.sortBy;
            rows.sort((a, b) => {
                const aValue = a?.[sortBy];
                const bValue = b?.[sortBy];

                if (sortBy === 'created_at') {
                    const aDate = aValue ? new Date(aValue).getTime() : 0;
                    const bDate = bValue ? new Date(bValue).getTime() : 0;
                    return aDate > bDate ? direction : -direction;
                }

                if (sortBy === 'total_amount') {
                    const aNum = Number(aValue ?? 0);
                    const bNum = Number(bValue ?? 0);
                    if (aNum === bNum) return 0;
                    return aNum > bNum ? direction : -direction;
                }

                const aText = (aValue || '').toString().toLowerCase();
                const bText = (bValue || '').toString().toLowerCase();
                if (aText === bText) return 0;
                return aText > bText ? direction : -direction;
            });

            return rows;
        },

        paginatedInvoiceRows() {
            const rows = this.filteredInvoiceRows();
            const totalPages = Math.max(1, Math.ceil(rows.length / this.invoiceTable.perPage));
            if (this.invoiceTable.page > totalPages) {
                this.invoiceTable.page = totalPages;
            }
            const start = (this.invoiceTable.page - 1) * this.invoiceTable.perPage;
            return rows.slice(start, start + this.invoiceTable.perPage);
        },

        invoiceMeta() {
            const rows = this.filteredInvoiceRows();
            const total = rows.length;
            const lastPage = Math.max(1, Math.ceil(total / this.invoiceTable.perPage));
            const page = Math.min(this.invoiceTable.page, lastPage);
            const from = total === 0 ? 0 : ((page - 1) * this.invoiceTable.perPage) + 1;
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

        invoiceFirstPage() {
            this.invoiceTable.page = 1;
        },

        invoicePreviousPage() {
            this.invoiceTable.page = Math.max(1, this.invoiceTable.page - 1);
        },

        invoiceNextPage() {
            const meta = this.invoiceMeta();
            this.invoiceTable.page = Math.min(meta.lastPage, this.invoiceTable.page + 1);
        },

        invoiceLastPage() {
            this.invoiceTable.page = this.invoiceMeta().lastPage;
        },

        viewInvoice(invoiceId) {
            if (invoiceId) {
                window.open('/admin/invoices/' + invoiceId, '_blank');
            }
        },

        invoiceStatusClass(status) {
            if (status === 'pending' || status === 'sent') return 'bg-amber-50 text-amber-700 ring-amber-200';
            if (status === 'accepted') return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            if (status === 'rejected' || status === 'cancelled') return 'bg-rose-50 text-rose-700 ring-rose-200';
            return 'bg-slate-50 text-slate-600 ring-slate-200';
        },

        assignmentStatusClass(status) {
            if (status === 'assigned') return 'bg-amber-50 text-amber-700 ring-amber-200';
            if (status === 'en_route') return 'bg-blue-50 text-blue-700 ring-blue-200';
            if (status === 'arrived') return 'bg-sky-50 text-sky-700 ring-sky-200';
            if (status === 'picking_up') return 'bg-teal-50 text-teal-700 ring-teal-200';
            if (status === 'completed') return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            if (status === 'cancelled') return 'bg-rose-50 text-rose-700 ring-rose-200';
            return 'bg-slate-50 text-slate-600 ring-slate-200';
        },

        exportInvoiceData(format) {
            const rows = this.filteredInvoiceRows();
            if (format === 'csv') {
                const header = ['Invoice Number', 'Status', 'Is Active', 'Total Amount', 'Created At'];
                const lines = rows.map(row => [
                    row.invoice_number,
                    row.status_label || row.status,
                    row.is_active ? 'Yes' : 'No',
                    row.total_amount ?? 0,
                    row.created_at || '',
                ]);
                const csv = [header, ...lines]
                    .map(columns => columns.map(value => `"${String(value ?? '').replace(/"/g, '""')}"`).join(','))
                    .join('\n');
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `shipment-${this.shipment.id}-invoices.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
                return;
            }

            if (format === 'json') {
                const blob = new Blob([JSON.stringify(rows, null, 2)], { type: 'application/json;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `shipment-${this.shipment.id}-invoices.json`;
                link.click();
                URL.revokeObjectURL(link.href);
                return;
            }

            if (format === 'print') {
                window.print();
            }
        },

        async createInvoice() {
            this.invoiceUiError = '';
            if (!this.canCreateInvoice()) {
                this.invoiceUiError = this.activeInvoiceBlockReason() || 'Cannot create invoice right now.';
                if (window.showToast) {
                    window.showToast(this.invoiceUiError, 'error');
                }
                return;
            }

            this.invoiceForm.submitting = true;
            try {
                const response = await fetch(this.config.invoiceStoreEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        pickup_fee: this.invoiceForm.pickup_fee,
                        transport_fee: this.invoiceForm.transport_fee,
                        handling_fee: this.invoiceForm.handling_fee,
                        other_fee: this.invoiceForm.other_fee,
                        notes: this.invoiceForm.notes,
                        send_now: this.invoiceForm.send_now ? 1 : 0
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to create invoice');
                }

                if (window.showToast) {
                    window.showToast('Invoice created successfully', 'success');
                }

                this.closeCreateInvoiceModal();
                // Reload page to reflect changes
                window.location.reload();
            } catch (error) {
                console.error('Create invoice error:', error);
                this.invoiceUiError = error.message || 'Failed to create invoice';
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to create invoice', 'error');
                }
            } finally {
                this.invoiceForm.submitting = false;
            }
        },

        buildInvoiceEndpoint(template, invoiceId) {
            return (template || '').replace('__INVOICE__', invoiceId);
        },

        async sendInvoice(invoiceId = null) {
            const targetId = invoiceId || this.invoice?.id;
            if (!targetId) return;

            try {
                const endpoint = this.buildInvoiceEndpoint(this.config.invoiceSendEndpointTemplate, targetId);
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to send invoice');
                }

                if (window.showToast) {
                    window.showToast(data.message || 'Invoice sent to vendor', 'success');
                }
                window.location.reload();
            } catch (error) {
                this.invoiceUiError = error.message || 'Failed to send invoice';
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to send invoice', 'error');
                }
            }
        },

        openCancelInvoiceModal(invoiceId = null) {
            const targetId = invoiceId || this.invoice?.id;
            if (!targetId) return;
            this.cancelInvoiceId = targetId;
            this.cancelInvoiceReason = '';
            this.cancelInvoiceLoading = false;
            this.showCancelInvoiceModal = true;
        },

        async confirmCancelInvoice() {
            if (!this.cancelInvoiceId) return;
            this.cancelInvoiceLoading = true;

            try {
                const endpoint = this.buildInvoiceEndpoint(this.config.invoiceCancelEndpointTemplate, this.cancelInvoiceId);
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        cancel_reason: this.cancelInvoiceReason.trim() || null
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to cancel invoice');
                }

                this.showCancelInvoiceModal = false;

                if (window.showToast) {
                    window.showToast(data.message || 'Invoice cancelled', 'success');
                }
                window.location.reload();
            } catch (error) {
                this.cancelInvoiceLoading = false;
                this.invoiceUiError = error.message || 'Failed to cancel invoice';
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to cancel invoice', 'error');
                }
            }
        },

        async adminAcceptInvoice(invoiceId = null) {
            const targetId = invoiceId || this.invoice?.id;
            if (!targetId) return;

            const confirmed = window.confirm(
                'Accept this invoice on behalf of the vendor?\n\n' +
                'This is an admin override action. The invoice will be marked as accepted ' +
                'and the shipment will proceed to the next stage.\n\n' +
                'This action will be recorded in the audit log.'
            );
            if (!confirmed) return;

            const adminNotes = window.prompt('Optional notes for this override (visible in audit log):') ?? '';
            if (adminNotes === null) return;

            try {
                const endpoint = `/admin/invoices/${targetId}/admin-accept`;
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ admin_notes: adminNotes || null })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to accept invoice');
                }

                if (window.showToast) {
                    window.showToast(data.message || 'Invoice accepted on behalf of vendor', 'success');
                }
                window.location.reload();
            } catch (error) {
                this.invoiceUiError = error.message || 'Failed to accept invoice';
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to accept invoice', 'error');
                }
            }
        },

        async loadAvailableDrivers() {
            this.assignmentForm.loadingDrivers = true;
            try {
                const endpoint = new URL(this.config.availableDriversEndpoint, window.location.origin);
                endpoint.searchParams.set('assignment_type', 'pickup');
                const response = await fetch(endpoint.toString());
                const data = await response.json();
                this.availableDrivers = data.data || data;
            } catch (error) {
                console.error('Failed to load available drivers:', error);
            } finally {
                this.assignmentForm.loadingDrivers = false;
            }
        },

        async loadAvailableWarehouses() {
            this.assignmentForm.loadingWarehouses = true;
            try {
                const response = await fetch(this.config.availableWarehousesEndpoint);
                const data = await response.json();
                this.availableWarehouses = data.data || data;
            } catch (error) {
                console.error('Failed to load available warehouses:', error);
            } finally {
                this.assignmentForm.loadingWarehouses = false;
            }
        },

        async loadAssignmentDependencies() {
            await Promise.all([
                this.loadAvailableDrivers(),
                this.loadAvailableWarehouses(),
            ]);
        },

        canUnassignCurrentAssignment() {
            if (!this.assignment) {
                return false;
            }

            if (this.assignment.cancelled_at || this.assignment.completed_at || this.assignment.picked_up_at) {
                return false;
            }

            return this.assignment.status !== 'cancelled' && this.assignment.status !== 'completed';
        },

        canReceiveCurrentAssignment() {
            if (!this.assignment) {
                return false;
            }

            if (this.assignment.cancelled_at || this.assignment.received_at) {
                return false;
            }

            return Boolean(this.assignment.picked_up_at || this.assignment.completed_at);
        },

        canEditCurrentAssignment() {
            if (!this.assignment) {
                return false;
            }

            if (this.assignment.cancelled_at || this.assignment.completed_at || this.assignment.picked_up_at) {
                return false;
            }

            return !['cancelled', 'completed'].includes(this.assignment.status);
        },

        canCreatePickupAssignment() {
            return this.canManage
                && !this.assignment
                && ['submitted', 'invoice_accepted'].includes(this.shipment?.status);
        },

        pickupAssignmentLockedLabel() {
            if (!this.assignment) {
                return 'Assign a pickup driver before pickup starts.';
            }

            if (this.assignment.picked_up_at || this.assignment.completed_at || ['picked_up', 'at_warehouse', 'sorted', 'in_transit', 'at_destination', 'out_for_delivery', 'delivered'].includes(this.shipment?.status)) {
                return 'Pickup has already been confirmed, so the driver can no longer be changed here.';
            }

            if (this.assignment.cancelled_at || this.assignment.status === 'cancelled') {
                return 'This pickup assignment was cancelled.';
            }

            return 'Driver can be changed until pickup is confirmed.';
        },

        assignmentStatusText() {
            const status = this.assignment?.status_label || this.assignment?.status || 'Unassigned';
            return String(status).replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
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

        resetAssignmentForm() {
            this.assignmentForm = {
                driver_id: '',
                target_warehouse_id: '',
                notes: '',
                submitting: false,
                loadingDrivers: false,
                loadingWarehouses: false
            };
        },

        async openAssignPickupDriver() {
            this.resetAssignmentForm();
            this.assignDriverModalOpen = true;
            await this.loadAssignmentDependencies();
        },

        normalizeAssignment(assignment) {
            if (!assignment) return null;

            const normalized = { ...assignment };
            const status = typeof normalized.status === 'object' && normalized.status?.value
                ? normalized.status.value
                : normalized.status;

            normalized.status = status || 'assigned';
            normalized.status_label = normalized.status_label || this.assignmentStatusText.call({ assignment: normalized });

            if (!normalized.driver && (normalized.driver_id || normalized.driver_name || normalized.driver_phone)) {
                normalized.driver = {
                    id: normalized.driver_id,
                    name: normalized.driver_name,
                    phone: normalized.driver_phone,
                };
            }

            if (!normalized.target_warehouse && normalized.targetWarehouse) {
                normalized.target_warehouse = normalized.targetWarehouse;
            }

            if (!normalized.target_warehouse && (normalized.target_warehouse_id || normalized.target_warehouse_name || normalized.warehouse_name)) {
                normalized.target_warehouse = {
                    id: normalized.target_warehouse_id,
                    name: normalized.target_warehouse_name || normalized.warehouse_name,
                    code: normalized.target_warehouse_code,
                };
            }

            return normalized;
        },

        applyAssignmentResponse(result) {
            const assignment = result?.data?.assignment;
            if (!assignment) return;

            this.assignment = this.normalizeAssignment(assignment);

            if (['submitted', 'invoice_accepted'].includes(this.shipment?.status)) {
                this.shipment.status = 'pickup_assigned';
            }
        },

        async openEditAssignment() {
            this.editAssignmentForm.driver_id = this.assignment?.driver_id ?? '';
            this.editAssignmentForm.target_warehouse_id = this.assignment?.target_warehouse_id ?? '';
            this.editAssignmentOpen = true;

            // Load available drivers for the edit form (all active, not busy — server filters)
            this.editAssignmentForm.loadingDrivers = true;
            this.editAssignmentForm.loadingWarehouses = true;
            try {
                const [driversRes, warehousesRes] = await Promise.all([
                    fetch(this.config.availableDriversEndpoint + '?assignment_type=pickup'),
                    fetch(this.config.availableWarehousesEndpoint),
                ]);
                const driversData = await driversRes.json();
                const warehousesData = await warehousesRes.json();

                // Include the current driver even if busy (since they're already assigned here)
                let drivers = driversData.data || [];
                const currentDriverId = this.assignment?.driver_id;
                if (currentDriverId && !drivers.find(d => d.id == currentDriverId)) {
                    const currentDriver = this.assignment?.driver;
                    if (currentDriver) {
                        drivers = [currentDriver, ...drivers];
                    }
                }
                this.availableDriversForEdit = drivers;
                this.availableWarehouses = warehousesData.data || [];
            } catch (e) {
                console.error('Failed to load edit assignment options:', e);
            } finally {
                this.editAssignmentForm.loadingDrivers = false;
                this.editAssignmentForm.loadingWarehouses = false;
            }
        },

        async updateAssignment() {
            if (!this.assignment || !this.assignment.id || !this.canEditCurrentAssignment()) {
                return;
            }

            this.editAssignmentForm.submitting = true;
            try {
                const endpoint = this.buildAssignmentEndpoint(this.config.updateAssignmentEndpointTemplate, this.assignment.id);
                const response = await fetch(endpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        driver_id: this.editAssignmentForm.driver_id || null,
                        target_warehouse_id: this.editAssignmentForm.target_warehouse_id || null,
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    this.applyAssignmentResponse(result);
                    this.editAssignmentOpen = false;
                    if (window.showToast) {
                        window.showToast(result.message || 'Assignment updated.', 'success');
                    }
                } else {
                    if (window.showToast) {
                        window.showToast(result.message || 'Failed to update assignment.', 'error');
                    }
                }
            } catch (e) {
                console.error('Failed to update assignment:', e);
                if (window.showToast) {
                    window.showToast('An unexpected error occurred.', 'error');
                }
            } finally {
                this.editAssignmentForm.submitting = false;
            }
        },

        assignmentStatusClass(status) {
            if (['assigned', 'pending'].includes(status)) return 'bg-amber-100 text-amber-700';
            if (['en_route', 'arrived'].includes(status)) return 'bg-blue-100 text-blue-700';
            if (['picking_up', 'completed'].includes(status)) return 'bg-emerald-100 text-emerald-700';
            if (status === 'cancelled') return 'bg-rose-100 text-rose-700';
            return 'bg-slate-100 text-slate-700';
        },

        assignmentStatusTextClass(status) {
            if (['assigned', 'pending'].includes(status)) return 'text-amber-700';
            if (['en_route', 'arrived'].includes(status)) return 'text-blue-700';
            if (['picking_up', 'completed'].includes(status)) return 'text-emerald-700';
            if (status === 'cancelled') return 'text-rose-700';
            return 'text-slate-700';
        },

        buildAssignmentEndpoint(template, assignmentId) {
            return (template || '').replace('__ASSIGNMENT__', assignmentId);
        },

        openUnassignModal() {
            if (!this.assignment || !this.assignment.id || !this.canUnassignCurrentAssignment()) {
                return;
            }
            this.unassignReason = '';
            this.showUnassignModal = true;
        },

        async confirmUnassign() {
            if (!this.unassignReason.trim()) {
                if (window.showToast) {
                    window.showToast('Unassignment reason is required.', 'error');
                }
                return;
            }

            this.assignmentActionLoading = true;
            this.assignmentUiError = '';
            try {
                const endpoint = this.buildAssignmentEndpoint(this.config.cancelAssignmentEndpointTemplate, this.assignment.id);
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        cancellation_reason: this.unassignReason.trim()
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to unassign driver');
                }

                this.showUnassignModal = false;

                if (window.showToast) {
                    window.showToast(data.message || 'Driver unassigned successfully', 'success');
                }

                window.location.reload();
            } catch (error) {
                this.assignmentUiError = error.message || 'Failed to unassign driver';
                if (window.showToast) {
                    window.showToast(this.assignmentUiError, 'error');
                }
            } finally {
                this.assignmentActionLoading = false;
            }
        },

        async receiveAtWarehouse() {
            if (!this.assignment || !this.assignment.id || !this.canReceiveCurrentAssignment()) {
                return;
            }

            let receivedWarehouseId = this.assignment.target_warehouse_id || null;
            if (!receivedWarehouseId) {
                const warehouseInput = window.prompt('Enter receiving warehouse ID (this assignment has no target warehouse):');
                if (warehouseInput === null) return;
                const parsed = Number.parseInt(warehouseInput, 10);
                if (!Number.isInteger(parsed) || parsed <= 0) {
                    if (window.showToast) {
                        window.showToast('A valid receiving warehouse ID is required.', 'error');
                    }
                    return;
                }
                receivedWarehouseId = parsed;
            }

            const receiveNotes = window.prompt('Optional receive notes (warehouse check):');
            if (receiveNotes === null) return;

            this.assignmentActionLoading = true;
            this.assignmentUiError = '';
            try {
                const endpoint = this.buildAssignmentEndpoint(this.config.receiveAssignmentEndpointTemplate, this.assignment.id);
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        received_warehouse_id: receivedWarehouseId,
                        receive_notes: receiveNotes.trim() || null
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to mark pickup as received');
                }

                if (window.showToast) {
                    window.showToast(data.message || 'Pickup received at warehouse', 'success');
                }

                window.location.reload();
            } catch (error) {
                this.assignmentUiError = error.message || 'Failed to mark pickup as received';
                if (window.showToast) {
                    window.showToast(this.assignmentUiError, 'error');
                }
            } finally {
                this.assignmentActionLoading = false;
            }
        },

        async assignDriver() {
            this.assignmentForm.submitting = true;
            try {
                const response = await fetch(this.config.assignDriverEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        driver_id: this.assignmentForm.driver_id,
                        target_warehouse_id: this.assignmentForm.target_warehouse_id,
                        notes: this.assignmentForm.notes
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to assign driver');
                }

                if (window.showToast) {
                    window.showToast('Driver assigned successfully', 'success');
                }

                this.applyAssignmentResponse(data);
                this.resetAssignmentForm();
                this.assignDriverModalOpen = false;
            } catch (error) {
                console.error('Assign driver error:', error);
                if (window.showToast) {
                    window.showToast(error.message || 'Failed to assign driver', 'error');
                }
            } finally {
                this.assignmentForm.submitting = false;
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

        formatMoney(amount, currency = 'GHS') {
            const value = Number(amount ?? 0);
            if (Number.isNaN(value)) {
                return `0.00 ${currency || 'GHS'}`;
            }
            return `${value.toFixed(2)} ${currency || 'GHS'}`;
        },

        async loadPayments() {
            try {
                const endpoint = this.config.paymentsDataEndpoint;
                const response = await fetch(endpoint, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                this.paymentsData = data;
                this.paymentsLoaded = true;
            } catch (e) {
                console.error('Failed to load payments:', e);
                this.paymentsLoaded = true;
            }
        },

        filteredPayments() {
            let list = this.paymentsData.payments || [];
            if (this.paymentSearch) {
                const q = this.paymentSearch.toLowerCase();
                list = list.filter(p =>
                    (p.payment_date || '').toLowerCase().includes(q) ||
                    (p.method_label || '').toLowerCase().includes(q) ||
                    (p.reference_number || '').toLowerCase().includes(q) ||
                    (p.recorded_by || '').toLowerCase().includes(q) ||
                    (p.invoice_number || '').toLowerCase().includes(q) ||
                    (p.notes || '').toLowerCase().includes(q) ||
                    String(p.amount).includes(q)
                );
            }
            const dir = this.paymentSortDir === 'asc' ? 1 : -1;
            const key = this.paymentSortBy;
            list = [...list].sort((a, b) => {
                const av = key === 'amount' ? parseFloat(a[key]) : (a[key] || '');
                const bv = key === 'amount' ? parseFloat(b[key]) : (b[key] || '');
                if (av < bv) return -1 * dir;
                if (av > bv) return 1 * dir;
                return 0;
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

        sortPayments(column) {
            if (this.paymentSortBy === column) {
                this.paymentSortDir = this.paymentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.paymentSortBy = column;
                this.paymentSortDir = 'asc';
            }
            this.paymentPage = 1;
        },

        togglePaymentColumn(key) {
            this.paymentVisibleColumns[key] = !this.paymentVisibleColumns[key];
        },

        exportPayments(format) {
            const data = this.filteredPayments();
            if (!data.length) { alert('No data to export'); return; }

            const rows = data.map(p => ({
                'Date': p.payment_date || '',
                'Amount': p.formatted_amount || p.amount || '',
                'Method': p.method_label || '',
                'Reference': p.reference_number || '',
                'Invoice': p.invoice_number || '',
                'Recorded By': p.recorded_by || '',
                'Notes': p.notes || '',
            }));

            if (format === 'csv') {
                const headers = Object.keys(rows[0]);
                const csvContent = [
                    headers.join(','),
                    ...rows.map(row =>
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
                link.download = 'shipment-payments.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }
        },

        printPayments() {
            const data = this.filteredPayments();
            if (!data.length) { alert('No data to print'); return; }

            const printWindow = window.open('', '_blank');
            if (!printWindow) { alert('Pop-up blocked. Please allow pop-ups to print.'); return; }

            const doc = printWindow.document;
            const headers = ['Date', 'Amount', 'Method', 'Reference', 'Invoice', 'Recorded By', 'Notes'];

            doc.title = 'Shipment Payments';
            doc.body.innerHTML = '';

            const style = doc.createElement('style');
            style.textContent = [
                'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; padding: 20px; }',
                'h1 { font-size: 24px; margin-bottom: 20px; color: #1e293b; }',
                'table { width: 100%; border-collapse: collapse; margin-top: 20px; }',
                'th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; font-size: 12px; }',
                'th { background-color: #f1f5f9; font-weight: 600; color: #475569; }',
                'tr:nth-child(even) { background-color: #f8fafc; }',
            ].join('\n');
            doc.head.appendChild(style);

            const title = doc.createElement('h1');
            title.textContent = 'Shipment Payments — ' + (this.shipment?.tracking_number || '');
            doc.body.appendChild(title);

            const meta = doc.createElement('p');
            meta.style.color = '#64748b';
            meta.style.fontSize = '14px';
            meta.style.marginBottom = '20px';
            meta.textContent = 'Generated on ' + new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            doc.body.appendChild(meta);

            const table = doc.createElement('table');
            const thead = doc.createElement('thead');
            const headRow = doc.createElement('tr');
            headers.forEach(h => { const th = doc.createElement('th'); th.textContent = h; headRow.appendChild(th); });
            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = doc.createElement('tbody');
            data.forEach(p => {
                const tr = doc.createElement('tr');
                ['GHS ' + (p.formatted_amount || p.amount), p.method_label, p.reference_number || '—', p.invoice_number || '—', p.recorded_by || '—', p.notes || '—'].forEach((val, i) => {
                    const td = doc.createElement('td');
                    td.textContent = i === 0 ? p.payment_date : val;
                    tr.appendChild(td);
                });
                // Fix: first column is date, then amount with currency
                tr.innerHTML = '';
                [p.payment_date, 'GHS ' + (p.formatted_amount || p.amount), p.method_label, p.reference_number || '—', p.invoice_number || '—', p.recorded_by || '—', p.notes || '—'].forEach(val => {
                    const td = doc.createElement('td');
                    td.textContent = val || '—';
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            doc.body.appendChild(table);

            setTimeout(() => printWindow.print(), 250);
        },

        async submitPayment() {
            this.paymentForm.submitting = true;
            try {
                const endpoint = this.config.storePaymentEndpoint;
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        amount: this.paymentForm.amount,
                        payment_method: this.paymentForm.payment_method,
                        reference_number: this.paymentForm.reference_number || null,
                        notes: this.paymentForm.notes || null,
                        payment_date: this.paymentForm.payment_date,
                    })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to record payment');
                if (window.showToast) window.showToast(data.message || 'Payment recorded', 'success');
                this.paymentForm = { open: false, submitting: false, amount: '', payment_method: '', reference_number: '', notes: '', payment_date: '' };
                await this.loadPayments();
            } catch (e) {
                console.error('Failed to record payment:', e);
                if (window.showToast) window.showToast(e.message || 'Failed to record payment', 'error');
            } finally {
                this.paymentForm.submitting = false;
            }
        },

        voidPayment(paymentId) {
            this.voidConfirm.paymentId = paymentId;
            this.voidConfirm.open = true;
        },

        async confirmVoidPayment() {
            this.voidConfirm.loading = true;
            try {
                const endpoint = (this.config.destroyPaymentEndpointTemplate || '').replace('__PAYMENT__', this.voidConfirm.paymentId);
                const response = await fetch(endpoint, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Failed to void payment');
                this.voidConfirm = { open: false, paymentId: null, loading: false };
                if (window.showToast) window.showToast(data.message || 'Payment voided', 'success');
                await this.loadPayments();
            } catch (e) {
                console.error('Failed to void payment:', e);
                if (window.showToast) window.showToast(e.message || 'Failed to void payment', 'error');
                this.voidConfirm.loading = false;
            }
        }
    };
}

function getShipmentShowConfig() {
    const container = document.querySelector('[data-shipment-show-config]');
    if (!container) return null;

    const rawConfig = container.getAttribute('data-shipment-show-config');
    if (!rawConfig) return null;

    try {
        return JSON.parse(rawConfig);
    } catch (error) {
        console.error('Invalid shipment show config JSON:', error);
        return null;
    }
}

function registerShipmentShowPage() {
    if (!window.Alpine) return;

    const config = getShipmentShowConfig();
    if (!config) return;

    window.shipmentShowConfig = config;
    Alpine.data('shipmentShow', shipmentShow);
}

if (window.Alpine) {
    registerShipmentShowPage();
} else {
    document.addEventListener('alpine:init', registerShipmentShowPage);
}
