/**
 * Pickup Assignments page Alpine component
 */

function buildPickupsTable(config) {
    return {
        endpoint: config.endpoint,
        availableDriversEndpoint: config.availableDriversEndpoint,
        availableWarehousesEndpoint: config.availableWarehousesEndpoint,
        updateEndpointTemplate: config.updateEndpointTemplate,
        cancelEndpointTemplate: config.cancelEndpointTemplate,
        receiveEndpointTemplate: config.receiveEndpointTemplate,
        assignEndpointTemplate: config.assignEndpointTemplate,
        csrfToken: '',

        assignments: [],
        meta: { current_page: 1, from: 0, to: 0, total: 0, last_page: 1 },

        loading: false,
        search: '',
        statusFilter: '',
        statusFilterName: 'All statuses',
        assignedFrom: '',
        assignedTo: '',
        dateRangePicker: null,
        perPage: 50,
        sortBy: 'assigned_at',
        sortDirection: 'desc',
        page: 1,

        columns: [
            { key: 'shipment', label: 'Shipment' },
            { key: 'vendor', label: 'Vendor' },
            { key: 'driver', label: 'Rider' },
            { key: 'warehouse', label: 'Target Warehouse' },
            { key: 'status', label: 'Status' },
            { key: 'vehicles', label: 'Required Vehicles' },
            { key: 'assigned_at', label: 'Assigned At' },
            { key: 'completed_at', label: 'Completed At' },
            { key: 'assigned_by', label: 'Assigned By' },
            { key: 'actions', label: 'Actions' },
        ],
        visibleColumns: {
            shipment: true,
            vendor: true,
            driver: true,
            warehouse: true,
            status: true,
            vehicles: true,
            assigned_at: true,
            completed_at: true,
            assigned_by: true,
            actions: true,
        },

        // Edit modal
        showEditModal: false,
        editSaving: false,
        editTarget: null,
        editForm: { driver_id: '', target_warehouse_id: '', reassignment_reason: '' },
        availableDrivers: [],
        availableWarehouses: [],
        editDriverSearch: '',
        editDriverPickerOpen: false,
        editDriverActiveIndex: -1,

        // Cancel modal
        showCancelModal: false,
        cancelSaving: false,
        cancelTarget: null,
        cancelReason: '',

        // Receive modal
        showReceiveModal: false,
        receiveSaving: false,
        receiveTarget: null,
        receiveForm: { received_warehouse_id: '', receive_notes: '' },

        // Dispatch Details (per-shipment multi-slot surface)
        showDispatchModal: false,
        dispatchTargetId: null,
        dispatchTarget: null,
        dispatchSlots: [],
        // One-shot message handed to the rebuilt slot rows after a successful
        // assign, so the result still lands on the slot that caused it.
        dispatchFlash: null,

        init() {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            this.csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
            this.initDateRange();
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    per_page: this.perPage,
                    search: this.search,
                    status: this.statusFilter,
                    sort: this.sortBy,
                    direction: this.sortDirection,
                    date_from: this.assignedFrom,
                    date_to: this.assignedTo,
                });

                const response = await fetch(`${this.endpoint}?${params}`);
                const data = await response.json();

                this.assignments = data.data;
                this.meta = data.meta;
                // Keep the open dispatch panel in step with the refreshed rows.
                this.refreshDispatchSlots();
            } catch (error) {
                console.error('Failed to load pickup assignments:', error);
            } finally {
                this.loading = false;
            }
        },

        sort(column) {
            if (this.sortBy === column) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = column;
                this.sortDirection = 'asc';
            }
            this.page = 1;
            this.loadData();
        },

        toggleColumn(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        // Pagination
        firstPage() { this.page = 1; this.loadData(); },
        previousPage() { if (this.page > 1) { this.page--; this.loadData(); } },
        nextPage() { if (this.page < this.meta.last_page) { this.page++; this.loadData(); } },
        lastPage() { this.page = this.meta.last_page; this.loadData(); },

        // Date range picker
        initDateRange() {
            const input = this.$refs.assignedRange;
            if (!input) return;

            const loadDateRangePicker = () => {
                if (window.$ && window.$.fn && window.$.fn.daterangepicker) {
                    this.setupDateRangePicker(input);
                    return;
                }
                const loadScript = (id, src) => new Promise(resolve => {
                    if (document.getElementById(id)) return resolve();
                    const s = document.createElement('script');
                    s.id = id; s.src = src; s.onload = resolve;
                    document.body.appendChild(s);
                });
                const cssId = 'daterangepicker-css';
                if (!document.getElementById(cssId)) {
                    const link = document.createElement('link');
                    link.id = cssId; link.rel = 'stylesheet';
                    link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                    document.head.appendChild(link);
                }
                loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                    .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                    .then(() => {
                        window.$ = window.jQuery = window.jQuery || window.$;
                        window.moment = window.moment || moment;
                        return loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js');
                    })
                    .then(() => this.setupDateRangePicker(input));
            };

            if (document.readyState === 'complete') {
                loadDateRangePicker();
            } else {
                window.addEventListener('load', loadDateRangePicker);
            }
        },

        setupDateRangePicker(input) {
            if (!window.$ || !window.$.fn || !window.$.fn.daterangepicker) return;

            window.$(input).daterangepicker({
                autoUpdateInput: false,
                alwaysShowCalendars: true,
                opens: 'left',
                locale: { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
                ranges: {
                    'Today':       [window.moment(), window.moment()],
                    'Yesterday':   [window.moment().subtract(1, 'days'), window.moment().subtract(1, 'days')],
                    'Last 7 Days': [window.moment().subtract(6, 'days'), window.moment()],
                    'Last 30 Days':[window.moment().subtract(29, 'days'), window.moment()],
                    'This Month':  [window.moment().startOf('month'), window.moment().endOf('month')],
                    'Last Month':  [window.moment().subtract(1, 'month').startOf('month'), window.moment().subtract(1, 'month').endOf('month')],
                },
            });

            window.$(input).on('apply.daterangepicker', (ev, picker) => {
                this.assignedFrom = picker.startDate.format('YYYY-MM-DD');
                this.assignedTo = picker.endDate.format('YYYY-MM-DD');
                input.value = this.assignedFrom + ' - ' + this.assignedTo;
                this.page = 1;
                this.loadData();
            });

            window.$(input).on('cancel.daterangepicker', () => {
                this.assignedFrom = '';
                this.assignedTo = '';
                input.value = '';
                this.page = 1;
                this.loadData();
            });
        },

        // Status badge styling
        statusBadgeClass(status) {
            const map = {
                assigned: 'bg-blue-100 text-blue-700',
                en_route: 'bg-amber-100 text-amber-700',
                arrived: 'bg-indigo-100 text-indigo-700',
                picking_up: 'bg-violet-100 text-violet-700',
                completed: 'bg-emerald-100 text-emerald-700',
                cancelled: 'bg-rose-100 text-rose-700',
            };
            return map[status] || 'bg-slate-100 text-slate-700';
        },

        // Coverage badge styling. Colour is a hint only — the label text is
        // always rendered alongside it, so the state is legible without colour.
        coverageBadgeClass(status) {
            const map = {
                unassigned: 'bg-slate-100 text-slate-600 ring-1 ring-slate-200/70',
                partially_assigned: 'bg-amber-100 text-amber-700 ring-1 ring-amber-200/70',
                fully_assigned: 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200/70',
            };
            return map[status] || 'bg-slate-100 text-slate-600';
        },

        // "2x Motorbike, 1x Aboboyaa" straight from the row's slot breakdown —
        // never recomputed here, the backend already derived it per shipment.
        requiredVehiclesText(row) {
            const breakdown = row?.slot_breakdown || [];
            if (!breakdown.length) return 'No vehicle request';
            return breakdown
                .map((entry) => `${entry.required}x ${entry.name}`)
                .join(', ');
        },

        // "2 of 3 slots filled" (or just the assigned count when no vehicle was
        // ever requested, which has no slot total to measure against).
        slotsFilledText(row) {
            if (!row) return '';
            if (!row.required_slots) {
                return `${row.assigned_slots || 0} assigned`;
            }
            return `${row.assigned_slots || 0} of ${row.required_slots} slots filled`;
        },

        // Dispatch Details — one row per requested slot, or the legacy single
        // rider when the shipment never requested a vehicle.
        async openDispatchModal(row) {
            this.dispatchTargetId = row.id;
            this.dispatchTarget = row;
            this.dispatchFlash = null;
            this.dispatchSlots = this.buildDispatchSlots(row);
            this.showDispatchModal = true;
            await this.loadDropdownData();
        },

        closeDispatchModal() {
            this.showDispatchModal = false;
            this.dispatchTargetId = null;
            this.dispatchTarget = null;
            this.dispatchSlots = [];
            this.dispatchFlash = null;
        },

        // Rebuild the slot rows from the current page rows, keeping the panel
        // pointing at the same assignment after a reload.
        refreshDispatchSlots() {
            if (!this.showDispatchModal) return;
            const fresh = this.assignments.find((a) => Number(a.id) === Number(this.dispatchTargetId));
            if (fresh) this.dispatchTarget = fresh;
            this.dispatchSlots = this.buildDispatchSlots(this.dispatchTarget);
            this.dispatchFlash = null;
        },

        // Live assignments for the open shipment, used to label filled slots.
        dispatchLiveAssignments() {
            const shipmentId = this.dispatchTarget?.shipment_id;
            if (!shipmentId) return [];
            return this.assignments.filter((a) => a.shipment_id === shipmentId && a.status !== 'cancelled');
        },

        buildDispatchSlots(row) {
            if (!row) return [];

            const live = this.dispatchLiveAssignments();
            const breakdown = row.slot_breakdown || [];
            const slots = [];

            // Legacy flow: no vehicle was requested, so there are no slots to
            // count. Keep offering the single-rider assign the shipment has
            // always used, alongside any riders already on it.
            if (!breakdown.length) {
                // Assignments are created in claim order, so id order is slot order.
                const existing = live.slice().sort((x, y) => (x.id || 0) - (y.id || 0));
                existing.forEach((assignment, index) => {
                    slots.push(this.buildFilledSlot({
                        key: `legacy-${assignment.id}`,
                        label: `Pickup Rider ${index + 1}`,
                        subtitle: 'Legacy pickup (no vehicle request)',
                        vehicleTypeId: null,
                        assignment,
                    }));
                });
                slots.push({
                    key: 'legacy-open',
                    label: 'Assign Rider',
                    subtitle: 'Legacy pickup (no vehicle request)',
                    vehicle_type_id: null,
                    filled: false,
                    unavailable: false,
                    driver_id: '',
                    target_warehouse_id: row.target_warehouse_id || '',
                    search: '',
                    open: false,
                    activeIndex: -1,
                    saving: false,
                    error: '',
                    success: '',
                });
                return slots;
            }

            let slotNumber = 1;
            breakdown.forEach((entry) => {
                const typeId = entry.vehicle_type_id ?? null;
                const required = Number(entry.required) || 0;
                const assigned = Number(entry.assigned) || 0;
                const matches = typeId
                    ? live
                        .filter((a) => Number(a.pickup_vehicle_type_id) === Number(typeId))
                        .sort((x, y) => (x.id || 0) - (y.id || 0))
                    : [];

                for (let i = 0; i < required; i++) {
                    const filled = i < assigned;
                    const match = filled ? matches[i] : null;
                    const label = `Slot ${slotNumber} - ${entry.name}`;
                    slotNumber++;

                    if (filled) {
                        slots.push(this.buildFilledSlot({
                            key: `type-${typeId}-${i}`,
                            label,
                            subtitle: 'Assigned',
                            vehicleTypeId: typeId,
                            assignment: match,
                        }));
                        continue;
                    }

                    // A requested type whose type row is gone (nullOnDelete) can
                    // never be claimed again, so it is offered as unavailable
                    // rather than as an assignable slot.
                    slots.push({
                        key: `type-${typeId}-${i}`,
                        label,
                        subtitle: typeId ? 'Awaiting rider' : 'Vehicle type no longer exists',
                        vehicle_type_id: typeId,
                        filled: false,
                        unavailable: !typeId,
                        driver_id: '',
                        target_warehouse_id: row.target_warehouse_id || '',
                        search: '',
                        open: false,
                        activeIndex: -1,
                        saving: false,
                        error: '',
                        success: '',
                    });
                }
            });

            // Hand the success message from the assign that just happened back to
            // the slot it filled, so the rebuild does not swallow the result.
            if (this.dispatchFlash) {
                const flash = this.dispatchFlash;
                const candidates = slots.filter((slot) => slot.filled
                    && (slot.vehicle_type_id ?? null) === (flash.typeId ?? null));
                if (candidates.length) {
                    candidates[candidates.length - 1].success = flash.message;
                }
            }

            return slots;
        },

        buildFilledSlot({ key, label, subtitle, vehicleTypeId, assignment }) {
            return {
                key,
                label,
                subtitle,
                vehicle_type_id: vehicleTypeId,
                filled: true,
                unavailable: false,
                assignment_id: assignment?.id ?? null,
                rider_name: assignment?.driver_name ?? 'Assigned',
                rider_phone: assignment?.driver_phone ?? '',
                canManage: !!assignment && !['completed', 'cancelled'].includes(assignment.status),
                driver_id: '',
                target_warehouse_id: '',
                search: '',
                open: false,
                activeIndex: -1,
                saving: false,
                error: '',
                success: '',
            };
        },

        // Rider picker, reused from the edit modal but scoped to one slot.
        slotDrivers(slot) {
            const query = String(slot.search || '').trim().toLowerCase();
            if (!query) return this.availableDrivers;
            return this.availableDrivers.filter((driver) => [driver.name, driver.phone, driver.vehicle_type, driver.vehicle_number]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(query)));
        },

        selectSlotDriver(slot, driver) {
            slot.driver_id = driver.id;
            slot.search = `${driver.name}${driver.phone ? ` / ${driver.phone}` : ''}`;
            slot.open = false;
            slot.activeIndex = -1;
            slot.error = '';
        },

        moveSlotDriverFocus(slot, direction) {
            const drivers = this.slotDrivers(slot);
            if (!drivers.length) return;
            slot.open = true;
            slot.activeIndex = slot.activeIndex < 0
                ? (direction > 0 ? 0 : drivers.length - 1)
                : (slot.activeIndex + direction + drivers.length) % drivers.length;
        },

        selectActiveSlotDriver(slot) {
            const driver = this.slotDrivers(slot)[slot.activeIndex];
            if (driver) this.selectSlotDriver(slot, driver);
        },

        // Claim the slot: send the slot's own vehicle type id so the backend
        // records which slot is being filled. Legacy slots send none, which is
        // the pre-existing single-rider path.
        async assignSlot(slot, confirmBusy = false) {
            slot.error = '';
            slot.success = '';

            if (!slot.driver_id) {
                slot.error = 'Select a rider first.';
                return;
            }
            if (!slot.target_warehouse_id) {
                slot.error = 'Select a target warehouse.';
                return;
            }

            slot.saving = true;
            try {
                const url = this.assignEndpointTemplate.replace('__ID__', this.dispatchTarget.shipment_id);
                const payload = {
                    driver_id: slot.driver_id,
                    target_warehouse_id: slot.target_warehouse_id,
                    confirm_busy_assignment: confirmBusy,
                };
                if (slot.vehicle_type_id) {
                    payload.pickup_vehicle_type_id = slot.vehicle_type_id;
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await response.json();

                if (response.status === 409 && data.code === 'rider_busy' && !confirmBusy) {
                    const work = data.data?.active_work || {};
                    if (window.confirm(`${data.message}\n\n${work.pickups || 0} pickup, ${work.transports || 0} transport, ${work.deliveries || 0} delivery.\n\nAssign anyway?`)) {
                        slot.saving = false;
                        return this.assignSlot(slot, true);
                    }
                    slot.error = data.message || 'Rider is busy.';
                    slot.saving = false;
                    return;
                }

                if (data.success) {
                    const message = data.message || 'Rider assigned successfully.';
                    slot.success = message;
                    if (window.showToast) window.showToast(message, 'success');
                    this.dispatchFlash = { typeId: slot.vehicle_type_id ?? null, message };
                    await this.loadData();
                } else {
                    // Backend refusal (all slots of a type already assigned, or a
                    // type that was never requested) stays on the slot that caused
                    // it rather than surfacing as a page-level error.
                    slot.error = data.message || 'Failed to assign rider.';
                }
            } catch (error) {
                console.error('Assign slot error:', error);
                slot.error = 'Failed to assign rider.';
            } finally {
                slot.saving = false;
            }
        },

        findAssignment(id) {
            return this.assignments.find((a) => Number(a.id) === Number(id)) || null;
        },

        openEditModalById(id) {
            const assignment = this.findAssignment(id);
            if (assignment) this.openEditModal(assignment);
        },

        openCancelModalById(id) {
            const assignment = this.findAssignment(id);
            if (assignment) this.openCancelModal(assignment);
        },

        // Edit modal
        async openEditModal(assignment) {
            this.editTarget = assignment;
            this.editForm.driver_id = assignment.driver_id;
            this.editForm.target_warehouse_id = assignment.target_warehouse_id;
            this.editForm.reassignment_reason = '';
            this.showEditModal = true;
            await this.loadDropdownData();
            if (assignment.driver_id && !this.availableDrivers.some((driver) => Number(driver.id) === Number(assignment.driver_id))) {
                this.availableDrivers.unshift({
                    id: assignment.driver_id,
                    name: assignment.driver_name || 'Current rider',
                    phone: assignment.driver_phone || '',
                    status: 'busy',
                    is_busy: true,
                    active_work_count: 1,
                    active_work: { pickups: 1, transports: 0, deliveries: 0 },
                });
            }
            const current = this.availableDrivers.find((driver) => Number(driver.id) === Number(assignment.driver_id));
            this.editDriverSearch = current ? `${current.name}${current.phone ? ` / ${current.phone}` : ''}` : '';
            this.editDriverPickerOpen = false;
            this.editDriverActiveIndex = -1;
        },

        async loadDropdownData() {
            if (this.availableDrivers.length && this.availableWarehouses.length) return;
            try {
                const [driversRes, warehousesRes] = await Promise.all([
                    fetch(this.availableDriversEndpoint),
                    fetch(this.availableWarehousesEndpoint),
                ]);
                const driversData = await driversRes.json();
                const warehousesData = await warehousesRes.json();
                this.availableDrivers = driversData.data || [];
                this.availableWarehouses = warehousesData.data || [];
            } catch (error) {
                console.error('Failed to load dropdown data:', error);
            }
        },

        driverOptionLabel(driver) {
            const current = Number(driver.id) === Number(this.editTarget?.driver_id);
            const state = current
                ? '✓ Assigned here'
                : (driver.is_busy ? `Busy · ${driver.active_work_count} active job${Number(driver.active_work_count) === 1 ? '' : 's'}` : 'Available');
            return `${state} — ${driver.name}${driver.phone ? ` (${driver.phone})` : ''}`;
        },

        filteredEditDrivers() {
            const query = String(this.editDriverSearch || '').trim().toLowerCase();
            if (!query) return this.availableDrivers;
            return this.availableDrivers.filter((driver) => [driver.name, driver.phone, driver.vehicle_type, driver.vehicle_number]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(query)));
        },

        selectEditDriver(driver) {
            this.editForm.driver_id = driver.id;
            this.editDriverSearch = `${driver.name}${driver.phone ? ` / ${driver.phone}` : ''}`;
            this.editDriverPickerOpen = false;
            this.editDriverActiveIndex = -1;
        },

        moveEditDriverFocus(direction) {
            const drivers = this.filteredEditDrivers();
            if (!drivers.length) return;
            this.editDriverPickerOpen = true;
            this.editDriverActiveIndex = this.editDriverActiveIndex < 0
                ? (direction > 0 ? 0 : drivers.length - 1)
                : (this.editDriverActiveIndex + direction + drivers.length) % drivers.length;
        },

        selectActiveEditDriver() {
            const driver = this.filteredEditDrivers()[this.editDriverActiveIndex];
            if (driver) this.selectEditDriver(driver);
        },

        async saveEdit(confirmBusy = false) {
            this.editSaving = true;
            try {
                const url = this.updateEndpointTemplate.replace('__ID__', this.editTarget.id);
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({ ...this.editForm, confirm_busy_assignment: confirmBusy }),
                });
                const data = await response.json();
                if (response.status === 409 && data.code === 'rider_busy' && !confirmBusy) {
                    const work = data.data?.active_work || {};
                    if (window.confirm(`${data.message}\n\n${work.pickups || 0} pickup, ${work.transports || 0} transport, ${work.deliveries || 0} delivery.\n\nAssign anyway?`)) {
                        this.editSaving = false;
                        return this.saveEdit(true);
                    }
                }
                if (data.success) {
                    this.showEditModal = false;
                    this.loadData();
                    if (window.showToast) window.showToast('Assignment updated successfully', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to update', 'error');
                }
            } catch (error) {
                console.error('Save edit error:', error);
                if (window.showToast) window.showToast('Failed to update assignment', 'error');
            } finally {
                this.editSaving = false;
            }
        },

        // Cancel modal
        openCancelModal(assignment) {
            this.cancelTarget = assignment;
            this.cancelReason = '';
            this.showCancelModal = true;
        },

        async confirmCancel() {
            this.cancelSaving = true;
            try {
                const url = this.cancelEndpointTemplate.replace('__ID__', this.cancelTarget.id);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({ cancellation_reason: this.cancelReason }),
                });
                const data = await response.json();
                if (data.success) {
                    this.showCancelModal = false;
                    this.loadData();
                    if (window.showToast) window.showToast('Assignment cancelled', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to cancel', 'error');
                }
            } catch (error) {
                console.error('Cancel error:', error);
                if (window.showToast) window.showToast('Failed to cancel assignment', 'error');
            } finally {
                this.cancelSaving = false;
            }
        },

        // Receive modal
        async openReceiveModal(assignment) {
            this.receiveTarget = assignment;
            this.receiveForm = { received_warehouse_id: '', receive_notes: '' };
            this.showReceiveModal = true;
            await this.loadDropdownData();
        },

        async confirmReceive() {
            this.receiveSaving = true;
            try {
                const url = this.receiveEndpointTemplate.replace('__ID__', this.receiveTarget.id);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(this.receiveForm),
                });
                const data = await response.json();
                if (data.success) {
                    this.showReceiveModal = false;
                    this.loadData();
                    if (window.showToast) window.showToast('Pickup received at warehouse', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed to receive', 'error');
                }
            } catch (error) {
                console.error('Receive error:', error);
                if (window.showToast) window.showToast('Failed to receive pickup', 'error');
            } finally {
                this.receiveSaving = false;
            }
        },

        // Export
        downloadCSV() {
            if (!this.assignments.length) return;
            const headers = ['Shipment #', 'Vendor', 'Rider', 'Phone', 'Target Warehouse', 'Status', 'Assigned At', 'Completed At', 'Assigned By'];
            const rows = this.assignments.map(a => [
                a.shipment_number, a.vendor_name, a.driver_name, a.driver_phone,
                a.target_warehouse, a.status_label, a.assigned_at || '', a.completed_at || '', a.assigned_by,
            ]);

            const csvContent = [
                headers.join(','),
                ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')),
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `pickup_assignments_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        printData() {
            if (!this.assignments.length) return;
            const printWindow = window.open('', '_blank');
            if (!printWindow) return;

            const doc = printWindow.document;
            doc.title = 'Pickup Assignments';
            doc.body.innerHTML = '';

            const style = doc.createElement('style');
            style.textContent = [
                'body { font-family: -apple-system, sans-serif; padding: 20px; }',
                'h1 { font-size: 24px; margin-bottom: 20px; color: #1e293b; }',
                'table { width: 100%; border-collapse: collapse; margin-top: 20px; }',
                'th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; font-size: 12px; }',
                'th { background-color: #f1f5f9; font-weight: 600; color: #475569; }',
                'tr:nth-child(even) { background-color: #f8fafc; }',
            ].join('\n');
            doc.head.appendChild(style);

            const title = doc.createElement('h1');
            title.textContent = 'Pickup Assignments';
            doc.body.appendChild(title);

            const headers = ['Shipment #', 'Vendor', 'Rider', 'Target Warehouse', 'Status', 'Assigned At', 'Completed At'];
            const table = doc.createElement('table');
            const thead = doc.createElement('thead');
            const headRow = doc.createElement('tr');
            headers.forEach(h => { const th = doc.createElement('th'); th.textContent = h; headRow.appendChild(th); });
            thead.appendChild(headRow);
            table.appendChild(thead);

            const tbody = doc.createElement('tbody');
            this.assignments.forEach(a => {
                const tr = doc.createElement('tr');
                [a.shipment_number, a.vendor_name, a.driver_name, a.target_warehouse, a.status_label, a.assigned_at || '-', a.completed_at || '-'].forEach(val => {
                    const td = doc.createElement('td');
                    td.textContent = val;
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            doc.body.appendChild(table);

            setTimeout(() => printWindow.print(), 250);
        },

        formatDateTime(value) {
            if (!value) return '-';
            const date = new Date(value);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    };
}

function getPickupsConfig() {
    const container = document.querySelector('[data-pickups-config]');
    if (!container) return null;

    try {
        return JSON.parse(container.getAttribute('data-pickups-config'));
    } catch (error) {
        console.error('Invalid pickups config JSON:', error);
        return null;
    }
}

function registerPickupsTable() {
    if (!window.Alpine) return;

    const config = getPickupsConfig();
    if (!config) return;

    Alpine.data('pickupsTable', () => buildPickupsTable(config));
}

if (window.Alpine) {
    registerPickupsTable();
} else {
    document.addEventListener('alpine:init', registerPickupsTable);
}
