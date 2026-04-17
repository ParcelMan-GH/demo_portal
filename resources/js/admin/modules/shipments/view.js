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

        // Duplicate
        duplicating: false,

        // Custody
        custodyLoaded: false,
        custody: { loading: false, labels: [], creatingRun: false },

        // Receiving
        receivingLoaded: false,
        receivingLightbox: null,
        receiving: { loading: false, saving: false, completingPickup: false, canReceive: false, packages: [], receipt: null, assignmentId: null },

        // Fulfillment type
        ftLoading: false,
        ftToast: '',
        ftToastType: 'success', // 'success' or 'error'
        _ftToastTimeout: null,

        activeTab: 'overview',

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
            if (this.shipment?.pickup_region_id && this.shipment?.pickup_district_id) {
                const parts = [
                    this.shipment?.pickup_region?.name,
                    this.shipment?.pickup_district?.name,
                    this.shipment?.pickup_town
                ].filter(Boolean);
                if (parts.length > 0) return parts.join(', ');
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

        async loadReceiving() {
            this.receiving.loading = true;
            this.receivingLoaded = true;
            try {
                const response = await fetch(this.config.receivingDataEndpoint, {
                    headers: { 'Accept': 'application/json' },
                });
                const result = await response.json();
                if (result.success) {
                    this.receiving.packages = (result.data.packages || []).map(pkg => ({
                        ...pkg,
                        _districts: [],
                    }));
                    this.receiving.canReceive = result.data.can_receive;
                    this.receiving.receipt = result.data.receipt;
                    this.receiving.assignmentId = result.data.assignment_id;
                    // Pre-load districts for packages that already have a region set
                    this.$nextTick(() => {
                        setTimeout(() => {
                            document.querySelectorAll('.rcv-district-select').forEach(distSel => {
                                const grid = distSel.closest('.grid');
                                const regionSel = grid?.querySelector('select');
                                if (regionSel && regionSel.value) {
                                    const pkgIndex = Array.from(document.querySelectorAll('.rcv-district-select')).indexOf(distSel);
                                    const pkg = this.receiving.packages[pkgIndex];
                                    if (pkg) this.loadPackageDistricts(pkg, regionSel);
                                }
                            });
                        }, 300);
                    });
                }
            } catch (e) { console.error('Failed to load receiving data', e); }
            this.receiving.loading = false;
        },

        async loadPackageDistricts(pkg, regionEl) {
            // regionEl is the <select>, parent is the <div>, parent's parent is the grid, find district select in siblings
            const distSel = regionEl ? regionEl.parentElement.parentElement.querySelector('.rcv-district-select') : null;

            const populate = (districts, selectedId) => {
                if (!distSel) return;
                distSel.innerHTML = '<option value="">Select District</option>';
                (districts || []).forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    if (String(d.id) === String(selectedId)) opt.selected = true;
                    distSel.appendChild(opt);
                });
            };

            if (!pkg.delivery_region_id) {
                populate([], null);
                pkg.delivery_district_id = null;
                return;
            }
            const savedId = pkg.delivery_district_id;
            try {
                const url = this.config.districtsUrl.replace('__REGION__', pkg.delivery_region_id);
                const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await resp.json();
                populate(json.data?.districts || [], savedId);
            } catch (e) {
                populate([], null);
            }
        },

        async receivePackage(pkg) {
            this.receiving.saving = true;
            const url = this.config.receiveSaveEndpoint.replace('__ITEM__', pkg.shipment_item_id);
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        received_quantity: pkg.received_quantity || 0,
                        damaged_quantity: pkg.damaged_quantity || 0,
                        condition_status: pkg.condition_status || 'ok',
                        notes: pkg.notes || null,
                        description: pkg.description || null,
                        delivery_recipient_name: pkg.delivery_recipient_name || null,
                        delivery_recipient_phone: pkg.delivery_recipient_phone || null,
                        delivery_region_id: pkg.delivery_region_id || null,
                        delivery_district_id: pkg.delivery_district_id || null,
                        delivery_town: pkg.delivery_town || null,
                        delivery_landmark: pkg.delivery_landmark || null,
                        delivery_instructions: pkg.delivery_instructions || null,
                    }),
                });
                const result = await response.json();
                if (result.success) {
                    if (result.data?.barcode_value) pkg.barcode_value = result.data.barcode_value;
                    if (result.data?.barcode_print_count) pkg.barcode_print_count = result.data.barcode_print_count;
                    window.showToast?.('Package received.', 'success');
                } else {
                    window.showToast?.(result.message || 'Failed.', 'error');
                }
            } catch (e) { window.showToast?.('Error receiving package.', 'error'); }
            this.receiving.saving = false;
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
                    if (result.data?.barcode_value) pkg.barcode_value = result.data.barcode_value;
                    if (result.data?.barcode_print_count) pkg.barcode_print_count = result.data.barcode_print_count;
                    if (result.data?.label_html) {
                        const w = window.open('', '_blank', 'width=400,height=600');
                        if (w) { w.document.write(result.data.label_html); w.document.close(); setTimeout(() => w.print(), 300); }
                    }
                    window.showToast?.('Label generated.', 'success');
                } else {
                    window.showToast?.(result.message || 'Failed.', 'error');
                }
            } catch (e) { window.showToast?.('Error printing label.', 'error'); }
        },

        finalizeConfirmOpen: false,

        openFinalizeConfirm() {
            this.finalizeConfirmOpen = true;
        },

        async finalizeReceiving() {
            this.finalizeConfirmOpen = false;
            this.receiving.saving = true;
            try {
                const response = await fetch(this.config.receiveFinalizeEndpoint, {
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
                    window.showToast?.('Receiving finalized!', 'success');
                    setTimeout(() => window.location.reload(), 1500);
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
            this.isSuperAdmin = this.config.isSuperAdmin ?? false;
            this.invoice = this.config.invoice;
            this.invoiceHistory = this.config.invoiceHistory || [];
            if (!this.invoice && this.invoiceHistory.length > 0) {
                const activeInvoice = this.invoiceHistory.find(row => !!row.is_active);
                this.invoice = activeInvoice || this.invoiceHistory[0];
            }
            this.assignment = this.config.assignment;
            this.assignmentHistory = this.config.assignmentHistory || [];
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
            return this.assignment && this.assignment.status === 'assigned';
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
                    if (window.showToast) {
                        window.showToast(result.message || 'Assignment updated.', 'success');
                    }
                    window.location.reload();
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

                this.assignDriverModalOpen = false;
                window.location.reload();
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
