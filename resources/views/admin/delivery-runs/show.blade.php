@extends($layoutName ?? 'admin.layouts.app')

@section('title', $run->run_number)
@section('breadcrumb-parent', 'Delivery Runs')
@section('breadcrumb-current', $run->run_number)

@php
use App\Models\Driver;
$deliveryDelayService = $deliveryDelayService ?? app(\App\Services\DeliveryDelayService::class);
$delayReasons = collect($delayReasons ?? $deliveryDelayService->activeReasons())->values();
$deliveryDrivers = $deliveryDrivers ?? Driver::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'vehicle_type', 'vehicle_number']);
$deliveryDriverOptions = $deliveryDrivers->map(fn ($driver) => [
    'id' => $driver->id,
    'name' => $driver->name,
    'phone' => $driver->phone,
    'vehicle_type' => $driver->vehicle_type,
    'vehicle_number' => $driver->vehicle_number,
    'meta' => collect([$driver->phone, $driver->vehicle_type, $driver->vehicle_number])->filter()->implode(' / '),
])->values();
$deliveryRunRoutes = $deliveryRunRoutes ?? [
    'indexUrl' => route('admin.delivery-runs.index'),
    'sortBatchUrl' => $run->sortBatch ? route('admin.sort-batches.show', $run->sortBatch) : null,
    'assignDriverUrl' => route('admin.delivery-runs.assign-driver', $run),
    'dispatchUrl' => route('admin.delivery-runs.dispatch', $run),
    'resendCodeUrlTemplate' => route('admin.delivery-runs.stops.resend-code', ['run' => $run->id, 'stop' => '__STOP__']),
    'updateStopDeliveryMethodUrlTemplate' => route('admin.delivery-runs.stops.update-delivery-method', ['run' => $run->id, 'stop' => '__STOP__']),
    'confirmHandoffStopUrlTemplate' => route('admin.delivery-runs.stops.confirm-handoff', ['run' => $run->id, 'stop' => '__STOP__']),
    'confirmHandoffItemUrlTemplate' => route('admin.delivery-runs.stops.items.confirm-handoff', ['run' => $run->id, 'stop' => '__STOP__', 'item' => '__ITEM__']),
];

$canAssignDriver = in_array($run->status, ['draft', 'assigned']);
$canDispatch     = $run->status === 'assigned' && $run->assignedDriver !== null;
$isDispatched    = in_array($run->status, ['out_for_delivery', 'partially_delivered', 'completed']);
$hideRunWarehouseMeta = $hideRunWarehouseMeta ?? false;
$totalStops      = $run->stops->count();
$totalItems      = $run->items->count();
$deliveredStops  = $run->stops->where('status', 'delivered')->count();
$pendingStops    = $run->stops->whereIn('status', ['pending', 'arrived'])->count();
$deliveredItems  = $run->items->where('status', 'delivered')->count();
$deliveryTimelineEvents = collect();
$addTimelineEvent = function (?\Illuminate\Support\Carbon $at, string $label, string $tone = 'slate', ?string $actor = null, ?string $detail = null, int $order = 50) use (&$deliveryTimelineEvents) {
    if (! $at) {
        return;
    }

    $deliveryTimelineEvents->push([
        'at' => $at,
        'order' => $order,
        'label' => $label,
        'tone' => $tone,
        'actor' => $actor,
        'detail' => $detail,
        'at_label' => $at->format('d M Y, h:i A'),
    ]);
};

$addTimelineEvent($run->created_at, 'Run created', 'slate', $run->createdBy?->name, $run->warehouse?->name, 10);
$addTimelineEvent($run->assigned_at, 'Rider assigned', 'blue', $run->assignedDriver?->name, $run->assignedDriver?->phone, 20);
$addTimelineEvent($run->dispatched_at, 'Run dispatched', 'amber', $run->assignedDriver?->name, $run->warehouse?->name, 30);

foreach ($run->stops as $timelineStop) {
    $stopLabel = $timelineStop->recipient_name ?: 'Stop #' . $timelineStop->id;
    $addTimelineEvent($timelineStop->verification_code_sent_at, 'OTP sent', 'cyan', null, $stopLabel, 40);
    $addTimelineEvent($timelineStop->arrived_at, 'Rider arrived at stop', 'blue', $run->assignedDriver?->name, $stopLabel, 50);
    $addTimelineEvent($timelineStop->handoff_at, 'Bus station handoff recorded', 'purple', $timelineStop->handoff_courier_name, $stopLabel, 60);
    $addTimelineEvent($timelineStop->delivered_at, $timelineStop->delivery_method === 'bus_handoff' ? 'Handoff completed' : 'Stop delivered', 'emerald', $run->assignedDriver?->name, $stopLabel, 70);
    $addTimelineEvent($timelineStop->confirmed_at, 'Stop confirmed', 'emerald', $timelineStop->confirmedBy?->name, $timelineStop->confirmation_notes, 80);
}

$addTimelineEvent($run->completed_at, 'Run completed', 'emerald', $run->assignedDriver?->name, "{$deliveredStops} of {$totalStops} stops delivered", 90);
$deliveryTimelineEvents = $deliveryTimelineEvents->sortByDesc(fn ($event) => sprintf('%03d-%s', $event['order'], $event['at']->timestamp))->values();
$latestTimelineEvent = $deliveryTimelineEvents->first();

$statusColors = match($run->status) {
    'draft'               => 'bg-slate-500/20 text-slate-300',
    'assigned'            => 'bg-blue-500/20 text-blue-300',
    'out_for_delivery'    => 'bg-amber-500/20 text-amber-300',
    'partially_delivered' => 'bg-orange-500/20 text-orange-300',
    'completed'           => 'bg-emerald-500/20 text-emerald-300',
    'cancelled'           => 'bg-red-500/20 text-red-300',
    default               => 'bg-slate-500/20 text-slate-300',
};
$dotColors = match($run->status) {
    'draft'               => 'bg-slate-400',
    'assigned'            => 'bg-blue-400',
    'out_for_delivery'    => 'bg-amber-400',
    'partially_delivered' => 'bg-orange-400',
    'completed'           => 'bg-emerald-400',
    'cancelled'           => 'bg-red-400',
    default               => 'bg-slate-400',
};
$stopStatusColors = [
    'pending'   => ['badge' => 'bg-slate-100 text-slate-600',   'dot' => 'bg-slate-400'],
    'arrived'   => ['badge' => 'bg-blue-100 text-blue-700',     'dot' => 'bg-blue-400'],
    'delivered' => ['badge' => 'bg-emerald-100 text-emerald-700','dot' => 'bg-emerald-400'],
    'failed'    => ['badge' => 'bg-red-100 text-red-700',       'dot' => 'bg-red-400'],
    'handed_off' => ['badge' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-400'],
];
$itemStatusColors = [
    'pending'   => 'bg-slate-100 text-slate-600',
    'delivered' => 'bg-emerald-100 text-emerald-700',
    'failed'    => 'bg-red-100 text-red-700',
    'partial'   => 'bg-orange-100 text-orange-700',
    'handed_off' => 'bg-orange-100 text-orange-700',
];
@endphp

@section('content')
    <div
        class="space-y-6"
        data-assign-driver-url="{{ $deliveryRunRoutes['assignDriverUrl'] }}"
        data-dispatch-url="{{ $deliveryRunRoutes['dispatchUrl'] }}"
        data-resend-code-url-template="{{ $deliveryRunRoutes['resendCodeUrlTemplate'] }}"
        data-eligible-items-url="{{ $deliveryRunRoutes['eligibleItemsUrl'] ?? '' }}"
        data-add-items-url="{{ $deliveryRunRoutes['addItemsUrl'] ?? '' }}"
        data-attach-sort-batch-url="{{ $deliveryRunRoutes['attachSortBatchUrl'] ?? '' }}"
        data-delay-notice-url-template="{{ $deliveryRunRoutes['delayNoticeItemUrlTemplate'] ?? '' }}"
        data-delay-reasons='@json($delayReasons)'
        data-driver-options='@json($deliveryDriverOptions)'
        data-local-delivery-batches='@json($localDeliveryBatches ?? [])'
        x-data="{
            actionLoading: false,
            delayLoading: false,
            delayModalOpen: false,
            showAssignModal: false,
            showDispatchConfirm: false,
            showNotesModal: false,
            showHandoffActionModal: false,
            delayTarget: null,
            delayReasons: [],
            delayForm: {
                reason_id: '',
                revised_eta: '',
                revised_eta_display: '',
                notify_recipient: true,
                notify_vendor: true,
                notify_vendor_sms: false,
                message: '',
                message_touched: false,
                notes: '',
            },
            handoffActionType: 'delivered',
            handoffActionStop: null,
            handoffActionNotes: '',
            draftView: 'build',
            builderMode: 'packages',
            localDeliveryBatches: [],
            selectedSortBatchId: '',
            eligibleItems: [],
            eligibleMeta: { total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0 },
            eligibleLoading: false,
            packageSearch: '',
            packageSearchTimer: null,
            selectedReceiptItemIds: [],
            selectedDriverId: '',
            selectedDriverLabel: '',
            driverSearch: '',
            driverDropdownOpen: false,
            drivers: [],
            currentDriverId: @js($run->assigned_driver_id),
            proofViewer: {
                open: false,
                title: '',
                subtitle: '',
                run: '',
                stopNumber: null,
                photos: [],
                index: 0,
            },
            init() {
                try {
                    this.drivers = JSON.parse(this.$root.dataset.driverOptions || '[]');
                } catch (error) {
                    this.drivers = [];
                }
                try {
                    this.localDeliveryBatches = JSON.parse(this.$root.dataset.localDeliveryBatches || '[]');
                } catch (error) {
                    this.localDeliveryBatches = [];
                }
                try {
                    this.delayReasons = JSON.parse(this.$root.dataset.delayReasons || '[]');
                } catch (error) {
                    this.delayReasons = [];
                }
            },
            csrfToken() {
                return document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
            },
            openBuilderMode(mode) {
                this.builderMode = mode;
                if (mode === 'packages' && this.eligibleItems.length === 0) {
                    this.loadEligibleRunItems(1);
                }
            },
            selectedSortBatch() {
                return this.localDeliveryBatches.find((batch) => String(batch.id) === String(this.selectedSortBatchId)) || null;
            },
            isPackageSelected(id) {
                return this.selectedReceiptItemIds.includes(Number(id));
            },
            togglePackageSelection(id) {
                const numericId = Number(id);
                const index = this.selectedReceiptItemIds.indexOf(numericId);
                if (index === -1) {
                    this.selectedReceiptItemIds.push(numericId);
                } else {
                    this.selectedReceiptItemIds.splice(index, 1);
                }
            },
            toggleAllEligible(event) {
                const ids = this.eligibleItems.map((item) => Number(item.warehouse_receipt_item_id));
                if (event.target.checked) {
                    this.selectedReceiptItemIds = Array.from(new Set([...this.selectedReceiptItemIds, ...ids]));
                    return;
                }
                this.selectedReceiptItemIds = this.selectedReceiptItemIds.filter((id) => !ids.includes(Number(id)));
            },
            allVisibleSelected() {
                return this.eligibleItems.length > 0 && this.eligibleItems.every((item) => this.isPackageSelected(item.warehouse_receipt_item_id));
            },
            onPackageSearch() {
                window.clearTimeout(this.packageSearchTimer);
                this.packageSearchTimer = window.setTimeout(() => this.loadEligibleRunItems(1), 300);
            },
            async loadEligibleRunItems(page = 1) {
                const endpoint = this.$root.dataset.eligibleItemsUrl;
                if (!endpoint) return;

                this.eligibleLoading = true;
                try {
                    const params = new URLSearchParams({
                        page: String(page || 1),
                        per_page: String(this.eligibleMeta.per_page || 20),
                    });
                    if (String(this.packageSearch || '').trim()) {
                        params.set('search', String(this.packageSearch).trim());
                    }
                    const response = await fetch(`${endpoint}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.message || 'Unable to load packages.');
                    }
                    this.eligibleItems = Array.isArray(result.data) ? result.data : [];
                    this.eligibleMeta = {
                        total: Number(result.meta?.total || 0),
                        per_page: Number(result.meta?.per_page || 20),
                        current_page: Number(result.meta?.current_page || page || 1),
                        last_page: Number(result.meta?.last_page || 1),
                        from: Number(result.meta?.from || 0),
                        to: Number(result.meta?.to || 0),
                    };
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to load packages.', 'error');
                } finally {
                    this.eligibleLoading = false;
                }
            },
            async addSelectedPackages() {
                if (!this.selectedReceiptItemIds.length) {
                    window.showToast?.('Select at least one package.', 'warning');
                    return;
                }
                this.actionLoading = true;
                try {
                    const response = await fetch(this.$root.dataset.addItemsUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ warehouse_receipt_item_ids: this.selectedReceiptItemIds }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Unable to add packages.');
                    }
                    window.showToast?.(result.message || 'Packages added to run.', 'success');
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to add packages.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
            async attachSortBatch() {
                if (!this.selectedSortBatchId) {
                    window.showToast?.('Select a local delivery batch.', 'warning');
                    return;
                }
                this.actionLoading = true;
                try {
                    const response = await fetch(this.$root.dataset.attachSortBatchUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ sort_batch_id: Number(this.selectedSortBatchId) }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Unable to add sort batch.');
                    }
                    window.showToast?.(result.message || 'Sort batch added to run.', 'success');
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to add sort batch.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
            openAssignRiderModal() {
                this.selectedDriverId = '';
                this.selectedDriverLabel = '';
                this.driverSearch = '';
                this.driverDropdownOpen = false;
                this.showAssignModal = true;
            },
            filteredDrivers() {
                const query = String(this.driverSearch || '').trim().toLowerCase();
                const availableDrivers = this.drivers.filter((driver) => Number(driver.id) !== Number(this.currentDriverId));
                if (!query) return availableDrivers;
                return availableDrivers.filter((driver) => {
                    return [driver.name, driver.phone, driver.vehicle_type, driver.vehicle_number, driver.meta]
                        .filter(Boolean)
                        .some((value) => String(value).toLowerCase().includes(query));
                });
            },
            selectDriver(driver) {
                this.selectedDriverId = driver.id;
                this.selectedDriverLabel = driver.name;
                this.driverSearch = driver.meta ? `${driver.name} / ${driver.meta}` : driver.name;
                this.driverDropdownOpen = false;
            },
            confirmDispatch() {
                this.showDispatchConfirm = true;
            },
            async assignDriver() {
                if (!this.selectedDriverId) {
                    window.showToast?.('Please select a rider first.', 'warning');
                    return;
                }
                this.actionLoading = true;
                try {
                    const response = await fetch(this.$root.dataset.assignDriverUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ driver_id: Number(this.selectedDriverId) }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to assign rider.');
                    }
                    window.showToast?.(result.message || 'Rider assigned successfully.', 'success');
                    this.showAssignModal = false;
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to assign rider.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
            async dispatchRun() {
                this.actionLoading = true;
                try {
                    const response = await fetch(this.$root.dataset.dispatchUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to dispatch run.');
                    }
                    window.showToast?.(result.message || 'Delivery run dispatched successfully.', 'success');
                    this.showDispatchConfirm = false;
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to dispatch delivery run.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
            async resendCode(stopId) {
                this.actionLoading = true;
                try {
                    const url = this.$root.dataset.resendCodeUrlTemplate.replace('__STOP__', stopId);
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to resend OTP.');
                    }
                    window.showToast?.(result.message || 'OTP code resent successfully.', 'success');
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to resend OTP.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
            openHandoffAction(stop, action) {
                this.handoffActionStop = stop;
                this.handoffActionType = action;
                this.handoffActionNotes = '';
                this.showHandoffActionModal = true;
            },
            closeHandoffAction(force = false) {
                if (this.actionLoading && !force) return;
                this.showHandoffActionModal = false;
                this.handoffActionStop = null;
                this.handoffActionNotes = '';
                this.handoffActionType = 'delivered';
            },
            delayClass(tone) {
                return {
                    rose: 'bg-rose-50 text-rose-700 ring-rose-200',
                    amber: 'bg-amber-50 text-amber-700 ring-amber-200',
                    blue: 'bg-blue-50 text-blue-700 ring-blue-200',
                    emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    slate: 'bg-slate-100 text-slate-600 ring-slate-200',
                }[tone || 'slate'] || 'bg-slate-100 text-slate-600 ring-slate-200';
            },
            openDelayModal(item) {
                this.delayTarget = item;
                this.delayForm = {
                    reason_id: this.delayReasons[0]?.id || '',
                    revised_eta: '',
                    revised_eta_display: '',
                    notify_recipient: true,
                    notify_vendor: true,
                    notify_vendor_sms: false,
                    message: '',
                    message_touched: false,
                    notes: '',
                };
                this.delayModalOpen = true;
                this.$nextTick(() => {
                    this.resetDelayEtaPicker();
                    this.ensureDelayDatePicker(() => {
                        this.initDelayEtaPicker();
                        this.updateDelayMessage(true);
                    });
                });
            },
            closeDelayModal() {
                if (!this.delayLoading) this.delayModalOpen = false;
            },
            delayPreview() {
                const reason = this.delayReasons.find((item) => String(item.id) === String(this.delayForm.reason_id))?.label || 'selected reason';
                const tracking = this.delayTarget?.tracking_code || this.delayTarget?.package_name || 'this package';
                const eta = this.delayForm.revised_eta_display ? ` New expected delivery: ${this.delayForm.revised_eta_display}.` : '';
                return `Delivery for package ${tracking} is delayed. Reason: ${reason}.${eta} We will update you if anything changes.`;
            },
            updateDelayMessage(force = false) {
                if (!force && this.delayForm.message_touched) return;
                this.delayForm.message = this.delayPreview();
            },
            ensureDelayDatePicker(callback) {
                const setup = () => callback?.();
                if (window.$ && window.moment && window.$.fn?.daterangepicker) {
                    setup();
                    return;
                }
                if (!document.getElementById('daterangepicker-css')) {
                    const link = document.createElement('link');
                    link.id = 'daterangepicker-css';
                    link.rel = 'stylesheet';
                    link.href = 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css';
                    document.head.appendChild(link);
                }
                const loadScript = (id, src) => new Promise((resolve) => {
                    if (document.getElementById(id)) return resolve();
                    const script = document.createElement('script');
                    script.id = id;
                    script.src = src;
                    script.onload = () => resolve();
                    document.body.appendChild(script);
                });
                loadScript('jquery-cdn', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js')
                    .then(() => loadScript('moment-cdn', 'https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'))
                    .then(() => loadScript('daterangepicker-cdn', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js'))
                    .then(setup);
            },
            initDelayEtaPicker() {
                if (!this.$refs.delayEtaInput || !window.$ || !window.moment || !window.$.fn?.daterangepicker) return;
                const input = window.$(this.$refs.delayEtaInput);
                if (input.data('daterangepicker')) return;
                input.daterangepicker({
                    singleDatePicker: true,
                    autoUpdateInput: false,
                    timePicker: true,
                    timePicker24Hour: false,
                    timePickerIncrement: 5,
                    minDate: window.moment(),
                    opens: 'left',
                    locale: { format: 'DD MMM YYYY, h:mm A', cancelLabel: 'Clear' },
                });
                input.on('apply.daterangepicker', (event, picker) => {
                    this.delayForm.revised_eta = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                    this.delayForm.revised_eta_display = picker.startDate.format('DD MMM YYYY, h:mm A');
                    input.val(this.delayForm.revised_eta_display);
                    this.updateDelayMessage();
                });
                input.on('cancel.daterangepicker', () => {
                    this.delayForm.revised_eta = '';
                    this.delayForm.revised_eta_display = '';
                    input.val('');
                    this.updateDelayMessage();
                });
            },
            resetDelayEtaPicker() {
                if (!this.$refs.delayEtaInput) return;
                const input = window.$ ? window.$(this.$refs.delayEtaInput) : null;
                if (input?.data('daterangepicker')) {
                    input.data('daterangepicker').remove();
                }
                this.$refs.delayEtaInput.value = '';
            },
            async sendDelayNotice() {
                if (!this.delayTarget || !this.delayForm.reason_id) return;
                const template = this.$root.dataset.delayNoticeUrlTemplate || '';
                if (!template) {
                    window.showToast?.('Delay notice endpoint is unavailable on this page.', 'error');
                    return;
                }
                this.delayLoading = true;
                try {
                    const response = await fetch(template.replace('__ITEM__', this.delayTarget.id), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            reason_id: this.delayForm.reason_id,
                            revised_eta: this.delayForm.revised_eta,
                            notify_recipient: this.delayForm.notify_recipient,
                            notify_vendor: this.delayForm.notify_vendor,
                            notify_vendor_sms: this.delayForm.notify_vendor_sms,
                            message: this.delayForm.message,
                            notes: this.delayForm.notes,
                        }),
                    });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok || result.success === false) {
                        throw new Error(result.message || 'Unable to send delay notice.');
                    }
                    window.showToast?.(result.message || 'Delay notice recorded.', 'success');
                    this.delayModalOpen = false;
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to send delay notice.', 'error');
                } finally {
                    this.delayLoading = false;
                }
            },
            handoffActionTitle() {
                if (this.handoffActionType === 'failed') return 'Mark Handoff Failed';
                if (this.handoffActionType === 'pending') return 'Mark Pending';
                return 'Confirm Delivered';
            },
            handoffActionDescription() {
                if (this.handoffActionType === 'failed') return 'Record that the recipient did not receive this bus handoff.';
                if (this.handoffActionType === 'pending') return 'Return this handoff to pending confirmation so it can be resolved again.';
                return 'Confirm the recipient received this bus handoff.';
            },
            handoffActionButtonLabel() {
                if (this.handoffActionType === 'failed') return 'Mark Failed';
                if (this.handoffActionType === 'pending') return 'Mark Pending';
                return 'Confirm Delivered';
            },
            async submitHandoffAction() {
                if (!this.handoffActionStop?.url) return;
                this.actionLoading = true;
                try {
                    const response = await fetch(this.handoffActionStop.url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: this.handoffActionType,
                            notes: this.handoffActionNotes,
                        }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to update handoff.');
                    }
                    window.showToast?.(result.message || 'Handoff updated successfully.', 'success');
                    this.closeHandoffAction(true);
                    window.location.reload();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to update handoff.', 'error');
                } finally {
                    this.actionLoading = false;
                }
            },
            openProofPhoto(payload) {
                const normalizePhoto = (photo) => {
                    const value = (typeof photo === 'string' || typeof photo === 'number') ? String(photo).trim() : '';
                    return value;
                };
                const photos = Array.isArray(payload?.photos)
                    ? payload.photos.map(normalizePhoto).filter((photo) => photo)
                    : [];
                const fallbackPhoto = typeof payload?.url === 'string' ? payload.url.trim() : '';
                const imageList = photos.length ? photos : (fallbackPhoto ? [fallbackPhoto] : []);
                if (!imageList.length) {
                    return;
                }
                this.proofViewer = {
                    open: true,
                    title: payload?.title || 'Proof photo',
                    subtitle: payload?.subtitle || '',
                    run: payload?.run || '',
                    stopNumber: payload?.stopNumber || null,
                    photos: imageList,
                    index: 0,
                };
            },
            closeProofPhoto() {
                this.proofViewer.open = false;
            },
            currentProofPhoto() {
                if (!this.proofViewer.open || !this.proofViewer.photos.length) return null;
                return this.proofViewer.photos[this.proofViewer.index] || null;
            },
            nextProofPhoto() {
                if (!this.proofViewer.photos.length || this.proofViewer.photos.length === 1) return;
                this.proofViewer.index = (this.proofViewer.index + 1) % this.proofViewer.photos.length;
            },
            previousProofPhoto() {
                if (!this.proofViewer.photos.length || this.proofViewer.photos.length === 1) return;
                this.proofViewer.index = (this.proofViewer.index - 1 + this.proofViewer.photos.length) % this.proofViewer.photos.length;
            },
        }"
    >

    <!-- Hero / Header Card -->
    <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 shadow-xl shadow-slate-300/20">
        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-[2rem]">
            <div class="absolute inset-y-0 right-0 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.25),transparent_58%)]"></div>
            <div class="absolute inset-y-0 left-0 w-1/2 bg-[radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.95),transparent_70%)]"></div>
        </div>

        <div class="relative p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <a href="{{ $deliveryRunRoutes['indexUrl'] }}" class="inline-flex h-11 w-auto shrink-0 items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 text-sm font-black text-slate-100 transition hover:bg-white/15">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Back</span>
                </a>

                <div class="ml-auto flex w-auto max-w-[calc(100%-5.75rem)] flex-wrap items-center justify-end gap-2 sm:max-w-none">
                    <span class="inline-flex h-9 items-center whitespace-nowrap rounded-full px-3 text-xs font-black {{ $statusColors }}">
                        <span class="mr-2 h-2 w-2 rounded-full {{ $dotColors }}"></span>
                        {{ $statusLabel }}
                    </span>
                    @if($canAssignDriver)
                        <button
                            type="button"
                            @@click="openAssignRiderModal()"
                            class="inline-flex h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border border-orange-400/45 bg-orange-500/15 px-3 text-xs font-black text-orange-100 transition hover:bg-orange-500/25"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7Z"/>
                            </svg>
                            {{ $run->assignedDriver ? 'Reassign Rider' : 'Assign Rider' }}
                        </button>
                    @endif
                    @if($run->sortBatch)
                        <a
                            href="{{ $deliveryRunRoutes['sortBatchUrl'] }}"
                            class="inline-flex h-9 max-w-full items-center rounded-full border border-orange-400/45 bg-orange-500/15 px-3 text-xs font-black text-orange-100 transition hover:bg-orange-500/25 sm:max-w-[420px]"
                            title="Open batch {{ $run->sortBatch->batch_number }}"
                        >
                            <span class="truncate">Batch: {{ $run->sortBatch->batch_number }}</span>
                        </a>
                    @endif
                    @if($run->notes)
                        <button
                            type="button"
                            @@click="showNotesModal = true"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/15 bg-white/10 text-slate-100 transition hover:bg-white/15"
                            title="View run notes"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-6 4h8M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="relative mt-7 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 lg:max-w-[760px] lg:shrink">
                <div class="flex min-w-0 items-start gap-4 sm:gap-5">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-orange-600 text-white shadow-xl shadow-orange-600/25 sm:h-24 sm:w-24">
                        <svg class="h-9 w-9 sm:h-12 sm:w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 17a2 2 0 11-4 0 2 2 0 014 0Zm10 0a2 2 0 11-4 0 2 2 0 014 0ZM3 7h11v10H9m-6 0V7m11 10h1m4 0h2v-5l-3-4h-4"/>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-orange-200">Delivery Run Workspace</p>
                        <h1 class="mt-2 max-w-4xl break-words text-3xl font-black leading-tight text-white sm:text-5xl xl:text-4xl 2xl:text-5xl">{{ $run->run_number }}</h1>
                        <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm font-bold text-slate-300 sm:text-base">
                            @unless($hideRunWarehouseMeta)
                                <span>{{ $run->warehouse?->name ?? 'No warehouse' }}</span>
                                @if($run->warehouse?->code)
                                    <span class="text-slate-500">/</span>
                                    <span>{{ $run->warehouse->code }}</span>
                                @endif
                                <span class="text-slate-500">/</span>
                            @endif
                            <span>{{ $run->assignedDriver?->name ? 'Rider: ' . $run->assignedDriver->name : 'No rider assigned' }}</span>
                            <span class="text-slate-500">/</span>
                            <span>Created by {{ $run->createdBy?->name ?? '—' }}</span>
                            <span class="text-slate-500">/</span>
                            <span>Created {{ $run->created_at->format('d M Y, h:i A') }}</span>
                        </div>

                        @if($canDispatch)
                            <div class="mt-5 flex flex-wrap gap-3">
                                @if($canDispatch)
                                    <button @@click="confirmDispatch()"
                                        :disabled="actionLoading"
                                        class="inline-flex h-12 items-center gap-2 rounded-2xl bg-emerald-600 px-5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                        <svg x-show="actionLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <svg x-show="!actionLoading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                        <span x-text="actionLoading ? 'Dispatching...' : 'Dispatch Run'"></span>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:ml-auto lg:w-[430px] lg:shrink-0 2xl:w-[480px]">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:p-4">
                        <p class="text-2xl font-black leading-tight text-white">{{ number_format($totalStops) }} stops</p>
                        <p class="mt-2 text-sm font-black leading-snug text-slate-400">{{ number_format($deliveredStops) }} delivered / {{ number_format($pendingStops) }} pending</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:p-4">
                        <p class="text-2xl font-black leading-tight text-white">{{ number_format($totalItems) }} packages</p>
                        <p class="mt-2 text-sm font-black leading-snug text-slate-400">{{ number_format($deliveredItems) }} delivered packages</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($latestTimelineEvent)
    <section x-data="{ timelineOpen: false }" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <button
            type="button"
            @@click="timelineOpen = !timelineOpen"
            class="flex w-full items-center justify-between gap-3 px-4 py-4 text-left transition hover:bg-slate-50 sm:px-5"
        >
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Delivery Timeline</p>
                <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2">
                    <span class="text-sm font-black text-slate-950">{{ $latestTimelineEvent['label'] }}</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-xs font-bold text-slate-500">{{ $latestTimelineEvent['at_label'] }}</span>
                    @if($latestTimelineEvent['actor'] ?? null)
                        <span class="text-slate-300">/</span>
                        <span class="truncate text-xs font-bold text-slate-500">by {{ $latestTimelineEvent['actor'] }}</span>
                    @endif
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600 sm:inline">{{ $deliveryTimelineEvents->count() }} events</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500">
                    <svg class="h-4 w-4 transition-transform" :class="timelineOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                    </svg>
                </span>
            </div>
        </button>

        <div x-show="timelineOpen" x-cloak x-transition.opacity.duration.150ms class="border-t border-slate-100 px-4 py-2 sm:px-5" style="display: none;">
            <div class="divide-y divide-slate-100">
                @foreach($deliveryTimelineEvents as $event)
                    @php
                        $timelineToneClasses = match($event['tone'] ?? 'slate') {
                            'blue' => 'bg-blue-500',
                            'purple' => 'bg-purple-500',
                            'emerald' => 'bg-emerald-500',
                            'amber' => 'bg-amber-500',
                            'cyan' => 'bg-cyan-500',
                            default => 'bg-slate-400',
                        };
                    @endphp
                    <div class="flex gap-3 py-3">
                        <div class="flex w-3 shrink-0 justify-center pt-1.5">
                            <span class="h-2 w-2 rounded-full {{ $timelineToneClasses }}"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm font-black text-slate-900">{{ $event['label'] }}</p>
                                <p class="text-xs font-bold text-slate-500">{{ $event['at_label'] }}</p>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                                @if($event['actor'] ?? null)
                                    <span>by {{ $event['actor'] }}</span>
                                @endif
                                @if(($event['actor'] ?? null) && ($event['detail'] ?? null))
                                    <span class="text-slate-300">/</span>
                                @endif
                                @if($event['detail'] ?? null)
                                    <span>{{ $event['detail'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if($run->status === 'draft')
    <section class="rounded-3xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                @@click="draftView = 'build'"
                class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="draftView === 'build' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Build Run
            </button>
            <button
                type="button"
                @@click="draftView = 'stops'"
                class="inline-flex w-auto items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black transition"
                :class="draftView === 'stops' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-slate-600 hover:bg-slate-50'"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.828 0l-4.243-4.243a8 8 0 1 1 11.314 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
                Preview Stops
            </button>
        </div>
    </section>
    @endif

    @if($run->status === 'draft' && !empty($deliveryRunRoutes['addItemsUrl']))
    <section x-show="draftView === 'build'" x-cloak class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7h-7m7 0v7m0-7-8 8-4-4-5 5"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-black text-slate-950">Build Delivery Run</h3>
                        <p class="mt-0.5 text-sm font-semibold text-slate-500">Add packages directly or import a sealed local delivery batch.</p>
                    </div>
                </div>

                <div class="flex rounded-2xl bg-slate-100 p-1">
                    <button type="button" @@click="openBuilderMode('packages')" class="rounded-xl px-4 py-2 text-sm font-black transition" :class="builderMode === 'packages' ? 'bg-white text-orange-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'">Packages</button>
                    <button type="button" @@click="openBuilderMode('batch')" class="rounded-xl px-4 py-2 text-sm font-black transition" :class="builderMode === 'batch' ? 'bg-white text-orange-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'">Sort Batch</button>
                </div>
            </div>
        </div>

        <div x-show="builderMode === 'packages'" x-init="loadEligibleRunItems(1)" class="p-4 sm:p-5">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative w-full lg:max-w-md">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="packageSearch" @@input="onPackageSearch()" placeholder="Search packages..." class="w-full rounded-xl border-2 border-slate-200 bg-white py-3 pl-10 pr-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
                <button type="button" @@click="addSelectedPackages()" :disabled="actionLoading || selectedReceiptItemIds.length === 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg x-show="actionLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Add Selected (<span x-text="selectedReceiptItemIds.length"></span>)
                </button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full table-auto divide-y divide-slate-200/60 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="w-12 px-4 py-3 text-left">
                                    <input type="checkbox" @@change="toggleAllEligible($event)" :checked="allVisibleSelected()" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                </th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Package</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Recipient</th>
                                <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Location</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Qty</th>
                                <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">Method</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-if="!eligibleLoading && eligibleItems.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No eligible warehouse packages found.</td>
                                </tr>
                            </template>
                            <template x-if="eligibleLoading">
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm font-semibold text-slate-500">Loading packages...</td>
                                </tr>
                            </template>
                            <template x-for="row in eligibleItems" :key="row.warehouse_receipt_item_id">
                                <tr @@click="togglePackageSelection(row.warehouse_receipt_item_id)" class="cursor-pointer hover:bg-orange-50/40">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" :checked="isPackageSelected(row.warehouse_receipt_item_id)" @@click.stop="togglePackageSelection(row.warehouse_receipt_item_id)" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                    </td>
                                    <td class="max-w-[320px] px-4 py-3">
                                        <p class="truncate text-sm font-black text-slate-900" x-text="row.item_description || '-'"></p>
                                        <div class="mt-1 flex items-center gap-2 text-[11px] font-semibold text-slate-500">
                                            <span x-text="row.shipment_number || '-'"></span>
                                            <span class="text-slate-300">/</span>
                                            <span class="font-mono" x-text="row.tracking_code || 'No tracking'"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-slate-800" x-text="row.destination?.recipient_name || '-'"></p>
                                        <p class="mt-1 text-[11px] font-semibold text-slate-500" x-text="row.destination?.recipient_phone || '-'"></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-700" x-text="row.destination?.town || '-'"></p>
                                        <p class="mt-1 text-[11px] text-slate-500" x-text="[row.destination?.district, row.destination?.region].filter(Boolean).join(', ') || '-'"></p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-black text-slate-700" x-text="row.received_quantity || 0"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-black text-slate-600" x-text="row.delivery_method_label || 'Direct Delivery'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 lg:hidden">
                    <template x-if="!eligibleLoading && eligibleItems.length === 0">
                        <div class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No eligible warehouse packages found.</div>
                    </template>
                    <template x-for="row in eligibleItems" :key="row.warehouse_receipt_item_id">
                        <button type="button" @@click="togglePackageSelection(row.warehouse_receipt_item_id)" class="flex w-full items-start gap-3 px-4 py-4 text-left">
                            <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded border" :class="isPackageSelected(row.warehouse_receipt_item_id) ? 'border-orange-600 bg-orange-600' : 'border-slate-300 bg-white'">
                                <svg x-show="isPackageSelected(row.warehouse_receipt_item_id)" class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 13 4 4L19 7"/></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex min-w-0 items-start justify-between gap-3">
                                    <span class="min-w-0 truncate text-sm font-black text-slate-900" x-text="row.item_description || '-'"></span>
                                    <span class="inline-flex shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600" x-text="'Qty ' + (row.received_quantity || 0)"></span>
                                </span>
                                <span class="mt-1 block text-xs font-semibold text-slate-500" x-text="(row.destination?.recipient_name || '-') + ' / ' + (row.destination?.town || '-')"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs font-bold text-slate-500">
                    Showing <span x-text="eligibleMeta.from || 0"></span> to <span x-text="eligibleMeta.to || 0"></span> of <span x-text="eligibleMeta.total || 0"></span>
                </p>
                <div class="flex items-center gap-2">
                    <button type="button" @@click="loadEligibleRunItems(eligibleMeta.current_page - 1)" :disabled="eligibleLoading || eligibleMeta.current_page <= 1" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 disabled:opacity-40">Previous</button>
                    <span class="text-xs font-black text-slate-600">Page <span x-text="eligibleMeta.current_page || 1"></span> / <span x-text="eligibleMeta.last_page || 1"></span></span>
                    <button type="button" @@click="loadEligibleRunItems(eligibleMeta.current_page + 1)" :disabled="eligibleLoading || eligibleMeta.current_page >= eligibleMeta.last_page" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 disabled:opacity-40">Next</button>
                </div>
            </div>
        </div>

        <div x-show="builderMode === 'batch'" x-cloak class="p-4 sm:p-5">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-[0.14em] text-slate-500">Sealed Local Delivery Batch</label>
                    <select x-model="selectedSortBatchId" class="w-full rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-sm font-bold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">Select a batch...</option>
                        <template x-for="batch in localDeliveryBatches" :key="batch.id">
                            <option :value="batch.id" x-text="`${batch.batch_number} / ${batch.items_count || 0} packages`"></option>
                        </template>
                    </select>
                    <p x-show="localDeliveryBatches.length === 0" class="mt-2 text-xs font-bold text-amber-700">No sealed local delivery batches are available.</p>
                    <p x-show="selectedSortBatch()" class="mt-2 text-xs font-semibold text-slate-500">
                        <span x-text="selectedSortBatch()?.items_count || 0"></span> packages
                        <span x-show="selectedSortBatch()?.sealed_at"> / sealed <span x-text="selectedSortBatch()?.sealed_at"></span></span>
                    </p>
                </div>
                <button type="button" @@click="attachSortBatch()" :disabled="actionLoading || !selectedSortBatchId" class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Add Batch
                </button>
            </div>
        </div>
    </section>
    @endif

    <!-- Stops Section -->
    <section class="space-y-4" @if($run->status === 'draft') x-show="draftView === 'stops'" x-cloak @endif>
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-950">{{ $run->status === 'draft' ? 'Preview Stops' : 'Delivery Stops' }}</h3>
                    <p class="mt-0.5 text-sm font-semibold text-slate-500">{{ $run->status === 'draft' ? 'Review how packages will be grouped before dispatch.' : 'Recipients and package handoffs in this run.' }}</p>
                </div>
            </div>
            <span class="inline-flex h-10 w-fit shrink-0 items-center whitespace-nowrap rounded-full border border-orange-200 bg-orange-50 px-4 text-sm font-black text-orange-700">
                {{ number_format($run->stops->count()) }} {{ $run->stops->count() === 1 ? 'stop' : 'stops' }}
            </span>
        </div>

        @if($run->stops->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm text-slate-400">No stops have been added to this run yet.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($run->stops->sortBy('id') as $index => $stop)
                @php
                    $stopBadgeClass = $stopStatusColors[$stop->status]['badge'] ?? 'bg-slate-100 text-slate-600';
                    $stopDotClass   = $stopStatusColors[$stop->status]['dot']   ?? 'bg-slate-400';
                    $updateStopDeliveryMethodUrl = str_replace('__STOP__', $stop->id, $deliveryRunRoutes['updateStopDeliveryMethodUrlTemplate']);
                    $confirmHandoffStopUrl = !empty($deliveryRunRoutes['confirmHandoffStopUrlTemplate'])
                        ? str_replace('__STOP__', $stop->id, $deliveryRunRoutes['confirmHandoffStopUrlTemplate'])
                        : null;
                    $stopStatusLabel = match($stop->status) {
                        'pending'   => 'Pending',
                        'arrived'   => 'Arrived',
                        'delivered' => 'Delivered',
                        'failed'    => 'Failed',
                        'handed_off' => 'Handed Off',
                        default     => ucwords(str_replace('_', ' ', $stop->status)),
                    };
    $plannedCoordinates = $stop->latitude && $stop->longitude ? $stop->latitude . ', ' . $stop->longitude : null;
    $deliveryCoordinates = $stop->delivery_latitude && $stop->delivery_longitude ? $stop->delivery_latitude . ', ' . $stop->delivery_longitude : null;
    $plannedCoordinatesUrl = $plannedCoordinates ? ('https://www.google.com/maps/search/?api=1&query=' . urlencode($plannedCoordinates)) : null;
    $deliveryCoordinatesUrl = $deliveryCoordinates ? ('https://www.google.com/maps/search/?api=1&query=' . urlencode($deliveryCoordinates)) : null;
    $proofPhotoUrl = $stop->proof_photo_path ? app(\App\Services\StorageService::class)->getUrl($stop->proof_photo_path) : null;
                    $deliveryMethodLabel = ($stop->delivery_method ?? 'direct') === 'bus_handoff' ? 'Bus Station Handoff' : 'Direct Delivery';
                    $locationParts = collect([$stop->town, $stop->district?->name, $stop->region?->name])->filter()->implode(', ');
                    $verificationSkippedAt = $stop->verification_skipped_at ? \Illuminate\Support\Carbon::parse($stop->verification_skipped_at) : null;
                @endphp
                <article class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <!-- Stop Header -->
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-5">
                        <div class="flex items-start gap-3">
                            <!-- Stop Number Bubble -->
                            <div class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-sm font-black text-orange-600 ring-1 ring-orange-100">
                                {{ $index + 1 }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-base font-black text-slate-950">{{ $stop->recipient_name }}</span>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $stopBadgeClass }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $stopDotClass }}"></span>
                                        {{ $stopStatusLabel }}
                                    </span>
                                    {{-- Delivery Method Badge + Toggle --}}
                                    <div x-data="{ method: '{{ $stop->delivery_method ?? 'direct' }}', changing: false }" class="inline-flex">
                                        <button @@click="
                                            if (changing) return;
                                            const newMethod = method === 'direct' ? 'bus_handoff' : 'direct';
                                            changing = true;
                                            fetch('{{ $updateStopDeliveryMethodUrl }}', {
                                                method: 'PATCH',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                                body: JSON.stringify({ delivery_method: newMethod })
                                            }).then(r => r.json()).then(j => {
                                                if (j.success) { method = newMethod; window.showToast?.(j.message, 'success'); }
                                                else window.showToast?.(j.message || 'Failed', 'error');
                                            }).catch(() => window.showToast?.('Error', 'error')).finally(() => changing = false);
                                        "
                                        :class="method === 'bus_handoff' ? 'bg-orange-50 text-orange-700 ring-orange-200' : 'bg-slate-50 text-slate-600 ring-slate-200'"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 cursor-pointer hover:opacity-80 transition-all"
                                        :title="method === 'bus_handoff' ? 'Click to change to Direct Delivery' : 'Click to change to Bus Handoff'">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            <span x-text="method === 'bus_handoff' ? 'Bus Station Handoff' : 'Direct Delivery'"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    @if($stop->recipient_phone)
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            {{ $stop->recipient_phone }}
                                        </span>
                                    @endif
                                    @if($locationParts)
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ $locationParts }}
                                        </span>
                                    @endif
                                    @if($stop->landmark)
                                        <span class="inline-flex items-center gap-1 text-slate-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21l1.9-5.7a8.5 8.5 0 113.8 3.8z"/>
                                            </svg>
                                            {{ $stop->landmark }}
                                        </span>
                                    @endif
                                    @if($stop->handoff_courier_phone)
                                        <span class="inline-flex items-center gap-1 text-violet-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            <a href="tel:{{ $stop->handoff_courier_phone }}" class="hover:underline">{{ $stop->handoff_courier_phone }}</a>
                                        </span>
                                    @endif
                                    @if($stop->handoff_vehicle_number)
                                        <span class="inline-flex items-center gap-1 text-violet-600 font-mono text-[10px]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            {{ $stop->handoff_vehicle_number }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Stop Meta (timestamps + OTP attempts) -->
                        <div class="flex flex-wrap items-center justify-end gap-2 text-xs text-slate-500 sm:flex-shrink-0">
                            @if(($stop->delivery_method ?? 'direct') === 'bus_handoff' && $confirmHandoffStopUrl)
                                <div class="flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto">
                                    @if($stop->status !== 'delivered')
                                    <button
                                        type="button"
                                        @@click="openHandoffAction({{ \Illuminate\Support\Js::from([
                                            'url' => $confirmHandoffStopUrl,
                                            'recipient' => $stop->recipient_name,
                                            'phone' => $stop->recipient_phone,
                                            'packages' => (int) $stop->total_packages,
                                            'courier' => $stop->handoff_courier_name,
                                            'handedOffAt' => $stop->handoff_at?->format('d M Y, h:i A'),
                                        ]) }}, 'delivered')"
                                        class="inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-xs font-black text-emerald-700 transition hover:bg-emerald-100 hover:text-emerald-800"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                                        </svg>
                                        Confirm Delivered
                                    </button>
                                    @endif
                                    @if($stop->status !== 'failed')
                                    <button
                                        type="button"
                                        @@click="openHandoffAction({{ \Illuminate\Support\Js::from([
                                            'url' => $confirmHandoffStopUrl,
                                            'recipient' => $stop->recipient_name,
                                            'phone' => $stop->recipient_phone,
                                            'packages' => (int) $stop->total_packages,
                                            'courier' => $stop->handoff_courier_name,
                                            'handedOffAt' => $stop->handoff_at?->format('d M Y, h:i A'),
                                        ]) }}, 'failed')"
                                        class="inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-black text-rose-700 transition hover:bg-rose-100 hover:text-rose-800"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                                        </svg>
                                        Mark Failed
                                    </button>
                                    @endif
                                    @if(in_array($stop->status, ['delivered', 'failed'], true))
                                    <button
                                        type="button"
                                        @@click="openHandoffAction({{ \Illuminate\Support\Js::from([
                                            'url' => $confirmHandoffStopUrl,
                                            'recipient' => $stop->recipient_name,
                                            'phone' => $stop->recipient_phone,
                                            'packages' => (int) $stop->total_packages,
                                            'courier' => $stop->handoff_courier_name,
                                            'handedOffAt' => $stop->handoff_at?->format('d M Y, h:i A'),
                                        ]) }}, 'pending')"
                                        class="inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-xl border border-amber-200 bg-amber-50 px-3 text-xs font-black text-amber-700 transition hover:bg-amber-100 hover:text-amber-800"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.64 18.36A9 9 0 0 0 18.36 5.64M18.36 5.64H14m4.36 0V10"/>
                                        </svg>
                                        Mark Pending
                                    </button>
                                    @endif
                                </div>
                            @endif
                            @if($stop->arrived_at)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Arrived {{ $stop->arrived_at->format('d M Y, h:i A') }}
                                </span>
                            @endif
                            @if($stop->delivered_at)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ ($stop->delivery_method ?? 'direct') === 'bus_handoff' ? 'Handed Off' : 'Delivered' }} {{ $stop->delivered_at->format('d M Y, h:i A') }}
                                </span>
                            @endif
                            @if($stop->verification_attempts > 0)
                                <span class="inline-flex items-center gap-1 text-amber-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    {{ $stop->verification_attempts }} OTP attempt(s)
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-3 px-4 py-4 sm:grid-cols-2 xl:grid-cols-4 sm:px-5">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Planned Location</p>
                            <p class="mt-1 text-sm font-black text-slate-900">{{ $locationParts ?: '—' }}</p>
                            @if($stop->gh_post_address || $stop->landmark)
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ collect([$stop->gh_post_address, $stop->landmark])->filter()->implode(' / ') }}</p>
                            @endif
                            @if($plannedCoordinates)
                                <a href="{{ $plannedCoordinatesUrl }}" target="_blank" rel="noopener noreferrer" class="mt-2 block truncate font-mono text-[11px] font-bold text-slate-500 underline decoration-dashed underline-offset-2 hover:text-slate-700">
                                    {{ $plannedCoordinates }}
                                </a>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Rider Captured</p>
                            <p class="mt-1 text-sm font-black text-slate-900">
                                @if($stop->delivered_at)
                                    {{ $stop->delivered_at->format('d M Y, h:i A') }}
                                @elseif($stop->arrived_at)
                                    Arrived {{ $stop->arrived_at->format('d M Y, h:i A') }}
                                @else
                                    —
                                @endif
                            </p>
                            @if($deliveryCoordinates)
                                <a href="{{ $deliveryCoordinatesUrl }}" target="_blank" rel="noopener noreferrer" class="mt-2 block truncate font-mono text-[11px] font-bold text-slate-500 underline decoration-dashed underline-offset-2 hover:text-slate-700">
                                    {{ $deliveryCoordinates }}
                                </a>
                            @endif
                            @if($stop->delivery_notes)
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $stop->delivery_notes }}</p>
                            @endif
                        </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Proof</p>
                                @if($proofPhotoUrl)
                                <a href="#"
                                    @@click.prevent="openProofPhoto({{ \Illuminate\Support\Js::from([
                                        'url' => $proofPhotoUrl,
                                        'title' => $stop->recipient_name ?: ('Stop ' . ($index + 1)),
                                        'subtitle' => $stop->recipient_phone,
                                        'run' => $run->run_number,
                                        'stopNumber' => $index + 1,
                                    ]) }})"
                                    class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-orange-700 transition hover:text-orange-900 hover:underline">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/>
                                    </svg>
                                    View proof photo
                                </a>
                            @else
                                <p class="mt-1 text-sm font-black text-slate-900">No proof photo</p>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Verification</p>
                            <p class="mt-1 text-sm font-black text-slate-900">{{ $deliveryMethodLabel }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">
                                {{ (int) $stop->verification_attempts }} OTP {{ (int) $stop->verification_attempts === 1 ? 'attempt' : 'attempts' }}
                                @if($stop->verification_skipped)
                                    / skipped
                                @endif
                            </p>
                            @if($verificationSkippedAt)
                                <p class="mt-1 text-[11px] font-semibold text-slate-500">Skipped {{ $verificationSkippedAt->format('d M Y, h:i A') }}</p>
                            @endif
                        </div>
                    </div>

                    @if(($stop->delivery_method ?? 'direct') === 'bus_handoff' || $stop->handoff_courier_name || $stop->bus_station_name)
                        <div class="mx-4 mb-4 rounded-2xl border border-orange-200 bg-orange-50/70 px-4 py-3 sm:mx-5">
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-orange-700">Bus Station Handoff</p>
                            <div class="mt-2 grid gap-3 text-sm font-semibold text-slate-700 sm:grid-cols-2 lg:grid-cols-4">
                                <span><span class="text-slate-400">Station:</span> {{ $stop->bus_station_name ?: '—' }}</span>
                                <span><span class="text-slate-400">Courier:</span> {{ $stop->handoff_courier_name ?: '—' }}</span>
                                <span><span class="text-slate-400">Phone:</span> {{ $stop->handoff_courier_phone ?: '—' }}</span>
                                <span><span class="text-slate-400">Vehicle:</span> {{ $stop->handoff_vehicle_number ?: '—' }}</span>
                            </div>
                            @if($stop->handoff_at || $stop->confirmed_at || $stop->confirmation_notes)
                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                                    @if($stop->handoff_at)<span>Handed off {{ $stop->handoff_at->format('d M Y, h:i A') }}</span>@endif
                                    @if($stop->confirmed_at)<span>Confirmed {{ $stop->confirmed_at->format('d M Y, h:i A') }}{{ $stop->confirmedBy?->name ? ' by ' . $stop->confirmedBy->name : '' }}</span>@endif
                                    @if($stop->confirmation_notes)<span>{{ $stop->confirmation_notes }}</span>@endif
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Items in this Stop -->
                    @if($stop->items->isNotEmpty())
                        <div class="px-4 pb-4 sm:px-5">
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Packages ({{ $stop->items->count() }})</p>
                            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                <div class="min-w-[900px]">
                                    <div class="grid grid-cols-12 gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2 text-[10px] font-black uppercase tracking-[0.14em] text-slate-500">
                                    <span class="col-span-4">Package</span>
                                    <span class="col-span-2">Tracking</span>
                                    <span class="col-span-1">Qty</span>
                                    <span class="col-span-1">Status</span>
                                    <span class="col-span-2">ETA</span>
                                    <span class="col-span-2 text-right">Actions</span>
                                    </div>
                                    @foreach($stop->items as $item)
                                    @php
                                        $itemBadgeClass = $itemStatusColors[$item->status] ?? 'bg-slate-100 text-slate-600';
                                        $confirmHandoffItemUrl = str_replace(['__STOP__', '__ITEM__'], [$stop->id, $item->id], $deliveryRunRoutes['confirmHandoffItemUrlTemplate']);
                                        $delaySnapshot = $deliveryDelayService->snapshot($item);
                                        $delayHistory = $deliveryDelayService->history($item);
                                        $delayToneClasses = match($delaySnapshot['tone'] ?? 'slate') {
                                            'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                            'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                            'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            default => 'bg-slate-100 text-slate-600 ring-slate-200',
                                        };
                                        $itemStatusLabel = match($item->status) {
                                        'pending'   => 'Pending',
                                        'delivered' => 'Delivered',
                                        'failed'    => 'Failed',
                                        'partial'   => 'Partial',
                                        'handed_off' => 'Handed Off',
                                        default     => ucwords(str_replace('_', ' ', $item->status)),
                                    };
                                    $itemLabel = $item->shipmentItem?->description ?: ('Package #' . $item->shipmentItem_id);
                                    $itemTrackingCode = $item->shipmentItem?->tracking_code;
                                    $vendorPhotos = collect($item->shipmentItem?->images ?? [])
                                        ->map(fn($photo) => [
                                            'url' => $photo->getSignedUrl()['url'] ?? null,
                                            'title' => 'Vendor photo',
                                        ])
                                        ->filter(fn($photo) => !empty($photo['url']))
                                        ->values();

                                    $driverPhotos = $proofPhotoUrl ? collect([
                                        ['url' => $proofPhotoUrl, 'title' => 'Driver photo'],
                                    ]) : collect();

                                    $receiptPhotos = collect($item->shipmentItem?->warehouseReceiptItems ?? [])
                                        ->flatMap(fn($receiptItem) => collect($receiptItem->photos ?? []))
                                        ->map(fn($photo) => [
                                            'url' => app(\App\Services\StorageService::class)->getUrl($photo->path),
                                            'title' => 'Receipt photo',
                                        ])
                                        ->filter(fn($photo) => !empty($photo['url']))
                                        ->values();

                                    $itemPhotos = $vendorPhotos->isNotEmpty()
                                        ? $vendorPhotos
                                        : ($driverPhotos->isNotEmpty() ? $driverPhotos : $receiptPhotos);
                                    $delayNoticePayload = [
                                        'id' => $item->id,
                                        'package_name' => $itemLabel,
                                        'tracking_code' => $itemTrackingCode,
                                        'eta' => $delaySnapshot,
                                    ];
                                    @endphp
                                    <div class="px-4 py-3 border-b border-slate-100 last:border-b-0">
                                        <div class="grid grid-cols-12 gap-3 items-center">
                                        <div class="col-span-12 min-w-0 sm:col-span-4">
                                            <p class="truncate text-sm font-black text-slate-900 leading-snug">{{ $itemLabel }}</p>
                                        </div>
                                        <div class="col-span-12 sm:col-span-2">
                                            <p class="truncate text-xs font-semibold text-slate-500">
                                                @if($itemTrackingCode)
                                                    {{ $itemTrackingCode }}
                                                @else
                                                    <span class="text-slate-400">No tracking</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="col-span-12 sm:col-span-1 min-w-0">
                                            <p class="truncate text-xs font-semibold text-slate-700">{{ $item->delivered_quantity ?? 0 }}/{{ $item->expected_quantity }}</p>
                                        </div>
                                        <div class="col-span-12 sm:col-span-1 min-w-0">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $itemBadgeClass }}">
                                                {{ $itemStatusLabel }}
                                            </span>
                                        </div>
                                        <div class="col-span-12 sm:col-span-2 min-w-0">
                                            <span class="inline-flex max-w-full items-center rounded-full px-2.5 py-1 text-[10px] font-black ring-1 {{ $delayToneClasses }}">
                                                <span class="truncate">{{ $delaySnapshot['label'] ?? 'No ETA' }}</span>
                                            </span>
                                            @if($delaySnapshot['expected_delivery_at'] ?? null)
                                                <p class="mt-1 truncate text-[11px] font-semibold text-slate-500">{{ $delaySnapshot['expected_delivery_at'] }}</p>
                                            @endif
                                            @if($delaySnapshot['last_notice_at'] ?? null)
                                                <p class="mt-1 truncate text-[10px] font-bold text-amber-700">Notice {{ $delaySnapshot['last_notice_at'] }}</p>
                                            @endif
                                        </div>
                                        <div class="col-span-12 flex flex-wrap items-center justify-end gap-2 sm:col-span-2 min-w-0">
                                            @if(($delaySnapshot['can_notify'] ?? false) && !empty($deliveryRunRoutes['delayNoticeItemUrlTemplate']))
                                                <button
                                                    type="button"
                                                    @@click.prevent="openDelayModal({{ \Illuminate\Support\Js::from($delayNoticePayload) }})"
                                                    class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-[11px] font-black text-amber-700 transition hover:bg-amber-100">
                                                    Delay
                                                </button>
                                            @endif
                                            @if($itemPhotos->isNotEmpty())
                                                <button
                                                    type="button"
                                                    @@click.prevent="openProofPhoto({
                                                        title: 'Package photos',
                                                        subtitle: @js($itemLabel),
                                                        run: @js($run->run_number),
                                                        stopNumber: @js($index + 1),
                                                        photos: @js($itemPhotos->pluck('url')->values()->toArray())
                                                    })"
                                                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-black text-slate-700 transition hover:bg-slate-50">
                                                    View photos
                                                </button>
                                            @else
                                                <span class="text-[11px] font-medium text-slate-400">No photos</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($item->notes)
                                        <p class="mt-3 text-[11px] font-semibold text-slate-500">Note: {{ $item->notes }}</p>
                                    @endif
                                    @if(!empty($delayHistory))
                                        <div class="mt-3 rounded-xl bg-slate-50 p-3 text-[11px] font-semibold text-slate-600">
                                            <p class="font-black uppercase tracking-[0.14em] text-slate-400">Latest delay activity</p>
                                            @foreach(collect($delayHistory)->take(2) as $event)
                                                <p class="mt-1">{{ collect([$event['source_label'] ?? null, $event['reason'] ?? null, $event['new_eta'] ?? null, $event['actor'] ?? null, $event['created_at'] ?? null])->filter()->implode(' / ') }}</p>
                                            @endforeach
                                        </div>
                                    @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="px-4 pb-4 sm:px-5">
                            <p class="text-xs text-slate-400 italic">No items at this stop.</p>
                        </div>
                    @endif



                    <!-- OTP Verification Attempts (if any) — hide for bus handoff -->
                    @if(($stop->delivery_method ?? 'direct') !== 'bus_handoff' && $stop->verificationAttempts->isNotEmpty())
                        <div class="ml-11 mt-3">
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">OTP Verification Attempts ({{ $stop->verificationAttempts->count() }})</p>
                            <div class="rounded-xl border border-amber-100 divide-y divide-amber-50 overflow-hidden">
                                @foreach($stop->verificationAttempts as $attempt)
                                <div class="px-4 py-2.5 flex items-center justify-between gap-2 bg-amber-50/40">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                        <span class="text-xs text-slate-700">
                                            Attempt #{{ $loop->iteration }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-500">
                                        {{ $attempt->created_at->format('d M Y, H:i:s') }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- OTP Code Display — hide for bus handoff --}}
                    @if(($stop->delivery_method ?? 'direct') !== 'bus_handoff' && $stop->verification_code_sent_at && $stop->status !== 'delivered')
                        @php
                            $otpCode = \App\Models\OtpCode::where('phone', (string) $stop->recipient_phone)
                                ->where('purpose', 'delivery_verification')
                                ->whereNull('verified_at')
                                ->where('expires_at', '>', now())
                                ->latest('created_at')
                                ->value('code');
                        @endphp
                        @if($otpCode)
                        <div class="mx-4 mb-4 flex items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 sm:mx-5">
                            <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            <div>
                                <span class="text-[10px] font-semibold text-indigo-500 uppercase tracking-wider">OTP Code</span>
                                <span class="ml-2 text-lg font-black text-indigo-700 tracking-[0.3em]">{{ $otpCode }}</span>
                            </div>
                            <span class="text-[10px] text-indigo-400 ml-auto">Sent {{ $stop->verification_code_sent_at->diffForHumans() }}</span>
                        </div>
                        @endif
                    @endif

                    <!-- Resend OTP Button — hide for bus handoff -->
                    @if(($stop->delivery_method ?? 'direct') !== 'bus_handoff' && $isDispatched && in_array($stop->status, ['pending', 'arrived']))
                    <div class="flex justify-end px-4 pb-4 sm:px-5">
                        <button @@click="resendCode({{ $stop->id }})"
                            :disabled="actionLoading"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="actionLoading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!actionLoading" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Resend OTP
                        </button>
                    </div>
                    @endif
                </article>
                @endforeach
            </div>
        @endif
    </section>

    <div x-show="delayModalOpen" x-cloak x-transition.opacity
         class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center overflow-y-auto p-3 sm:p-4"
         @@keydown.escape.window="closeDelayModal()">
        <div class="fixed inset-0 min-h-dvh bg-slate-950/60 backdrop-blur-sm" @@click="closeDelayModal()"></div>
        <div @@click.stop class="relative my-auto flex max-h-[88dvh] w-full max-w-lg flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-2xl font-black leading-tight text-slate-950">Send Delay Notice</h3>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-500" x-text="delayTarget?.tracking_code || delayTarget?.package_name || 'Package delay'"></p>
                    </div>
                </div>
                <button type="button" @@click="closeDelayModal()" :disabled="delayLoading"
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">Delay reason</label>
                    <select x-model="delayForm.reason_id" @@change="updateDelayMessage()" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-black text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                        <option value="">Select reason</option>
                        <template x-for="reason in delayReasons" :key="reason.id">
                            <option :value="reason.id" x-text="reason.label"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">Revised ETA <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                    <input type="text" x-ref="delayEtaInput" readonly placeholder="Select date and time" class="w-full cursor-pointer rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                </div>
                <div class="grid gap-2 sm:grid-cols-3">
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-bold text-slate-700"><input type="checkbox" x-model="delayForm.notify_recipient" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500"> Recipient SMS</label>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-bold text-slate-700"><input type="checkbox" x-model="delayForm.notify_vendor" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500"> Vendor app</label>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-bold text-slate-700"><input type="checkbox" x-model="delayForm.notify_vendor_sms" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500"> Vendor SMS</label>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">Notes</label>
                    <textarea x-model="delayForm.notes" rows="3" class="w-full rounded-2xl border-2 border-slate-200 bg-white px-4 py-4 text-base font-semibold text-slate-900 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Internal note"></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">Message</label>
                    <textarea x-model="delayForm.message" @@input="delayForm.message_touched = true" rows="4" class="w-full rounded-2xl border-2 border-amber-200 bg-amber-50 px-4 py-4 text-base font-semibold leading-7 text-amber-950 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100" placeholder="Message to send"></textarea>
                    <p class="mt-1 text-xs font-semibold text-slate-400">You can adjust this message before sending.</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                <button type="button" @@click="closeDelayModal()" :disabled="delayLoading" class="rounded-xl border-2 border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Cancel</button>
                <button type="button" @@click="sendDelayNotice()" :disabled="delayLoading || !delayForm.reason_id" class="rounded-xl border-2 border-orange-600 bg-orange-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:border-orange-700 hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40">
                    <span x-text="delayLoading ? 'Sending...' : 'Send Notice'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Proof Photo Viewer Modal --}}
    <template x-teleport="body">
        <template x-if="proofViewer.open">
            <div
                class="fixed inset-0 z-[230] flex min-h-dvh w-screen items-center justify-center bg-slate-950/95 p-3"
                @@keydown.escape.window="closeProofPhoto()"
                @@keydown.arrow-right.window="nextProofPhoto()"
                @@keydown.arrow-left.window="previousProofPhoto()">

                <button type="button" class="absolute inset-0 cursor-zoom-out" @@click="closeProofPhoto()" aria-label="Close proof photo viewer"></button>

                <div class="pointer-events-none absolute left-0 right-0 top-0 z-10 bg-gradient-to-b from-slate-950 via-slate-950/70 to-transparent px-4 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 text-white">
                            <h3 class="truncate text-base font-black sm:text-xl" x-text="proofViewer.title || 'Proof photo'"></h3>
                            <p class="mt-1 truncate text-xs font-black text-slate-300 sm:text-sm" x-text="proofViewer.subtitle || 'Run ' + proofViewer.run"></p>
                            <p class="mt-1 truncate text-[11px] font-semibold text-slate-400" x-text="proofViewer.stopNumber ? `Stop ${proofViewer.stopNumber}` : ''"></p>
                        </div>
                        <button type="button"
                            @@click="closeProofPhoto()"
                            class="pointer-events-auto flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 transition hover:bg-white/20"
                            aria-label="Close proof photo viewer">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div @@click.stop class="relative z-[1] flex h-full w-full items-center justify-center px-1 py-24 sm:px-14">
                    <template x-if="currentProofPhoto()">
                        <img :src="currentProofPhoto()" :alt="proofViewer.title || 'Proof photo'" class="max-h-full max-w-full object-contain shadow-2xl">
                    </template>

                    <button type="button"
                        x-show="proofViewer.photos.length > 1"
                        @@click="previousProofPhoto()"
                        class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/20 sm:left-5">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button type="button"
                        x-show="proofViewer.photos.length > 1"
                        @@click="nextProofPhoto()"
                        class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-white/20 sm:right-5">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <div class="absolute bottom-0 left-0 right-0 z-10 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent px-4 pb-4 pt-10 sm:px-6">
                    <p class="text-xs font-black text-slate-300">
                        <span>Photo </span>
                        <span x-text="proofViewer.index + 1"></span>
                        <span> of </span>
                        <span x-text="proofViewer.photos.length"></span>
                    </p>
                </div>
            </div>
        </template>
    </template>

    @if($run->notes)
    <!-- Run Notes Modal -->
    <div x-show="showNotesModal" x-cloak
         class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center overflow-y-auto p-3 sm:p-4"
         @@keydown.escape.window="showNotesModal = false">
        <div class="fixed inset-0 min-h-dvh bg-slate-950/60 backdrop-blur-sm" @@click="showNotesModal = false"></div>
        <div class="relative my-auto flex max-h-[88dvh] w-full max-w-lg flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m-6 4h8M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-2xl font-black leading-tight text-slate-950">Run Notes</h3>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $run->run_number }}</p>
                    </div>
                </div>
                <button @@click="showNotesModal = false"
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <p class="whitespace-pre-line text-base font-semibold leading-8 text-slate-800">{{ $run->notes }}</p>
            </div>
            <div class="flex items-center justify-end gap-3 rounded-b-[2rem] border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                <button @@click="showNotesModal = false"
                    class="inline-flex h-12 items-center justify-center whitespace-nowrap rounded-2xl bg-slate-900 px-6 text-sm font-black text-white shadow-lg shadow-slate-900/15 transition hover:bg-slate-800">
                    Done
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Bus Handoff Confirmation Modal -->
    <div x-show="showHandoffActionModal" x-cloak
         class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center overflow-y-auto p-3 sm:p-4"
         @@keydown.escape.window="closeHandoffAction()">
        <div class="fixed inset-0 min-h-dvh bg-slate-950/60 backdrop-blur-sm" @@click="closeHandoffAction()"></div>
        <div class="relative my-auto flex max-h-[88dvh] w-full max-w-lg flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex min-w-0 items-start gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-white shadow-xl"
                        :class="{
                            'bg-rose-600 shadow-rose-600/20': handoffActionType === 'failed',
                            'bg-amber-500 shadow-amber-500/20': handoffActionType === 'pending',
                            'bg-emerald-600 shadow-emerald-600/20': handoffActionType === 'delivered'
                        }"
                    >
                        <svg x-show="handoffActionType === 'delivered'" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                        </svg>
                        <svg x-show="handoffActionType === 'failed'" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                        </svg>
                        <svg x-show="handoffActionType === 'pending'" class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.64 18.36A9 9 0 0 0 18.36 5.64M18.36 5.64H14m4.36 0V10"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-2xl font-black leading-tight text-slate-950" x-text="handoffActionTitle()"></h3>
                        <p class="mt-1 text-sm font-semibold leading-6 text-slate-500" x-text="handoffActionDescription()"></p>
                    </div>
                </div>
                <button @@click="closeHandoffAction()"
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto bg-slate-50/70 px-5 py-5 sm:px-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Recipient</p>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-base font-black text-slate-950" x-text="handoffActionStop?.recipient || '-'"></p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-500" x-text="handoffActionStop?.phone || '-'"></p>
                        </div>
                        <span class="inline-flex w-fit shrink-0 rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700" x-text="`${handoffActionStop?.packages || 0} package(s)`"></span>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Courier</p>
                            <p class="mt-0.5 font-bold text-slate-800" x-text="handoffActionStop?.courier || '-'"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Handed Off</p>
                            <p class="mt-0.5 font-bold text-slate-800" x-text="handoffActionStop?.handedOffAt || '-'"></p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Notes <span class="font-semibold normal-case tracking-normal text-slate-400">(optional)</span></label>
                    <textarea
                        x-model="handoffActionNotes"
                        rows="4"
                        placeholder="Add confirmation notes..."
                        class="w-full resize-none rounded-2xl border-2 border-slate-200 bg-white px-4 py-3 text-base font-black text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-100 sm:text-sm"
                    ></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 rounded-b-[2rem] border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                <button @@click="closeHandoffAction()"
                    class="inline-flex h-12 items-center justify-center whitespace-nowrap rounded-2xl border border-slate-200 bg-white px-5 text-sm font-black text-slate-700 transition hover:bg-slate-100 sm:h-14 sm:px-7">
                    Cancel
                </button>
                <button
                    @@click="submitHandoffAction()"
                    :disabled="actionLoading || !handoffActionStop?.url"
                    class="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-2xl px-5 text-sm font-black text-white shadow-lg transition disabled:cursor-not-allowed disabled:opacity-50 sm:h-14 sm:px-8"
                    :class="{
                        'bg-rose-600 shadow-rose-600/20 hover:bg-rose-700': handoffActionType === 'failed',
                        'bg-amber-500 shadow-amber-500/20 hover:bg-amber-600': handoffActionType === 'pending',
                        'bg-emerald-600 shadow-emerald-600/20 hover:bg-emerald-700': handoffActionType === 'delivered'
                    }"
                >
                    <svg x-show="actionLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="actionLoading ? 'Saving...' : handoffActionButtonLabel()"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Assign Rider Modal -->
    <div x-show="showAssignModal" x-cloak
         class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center overflow-y-auto p-3 sm:p-4"
         @@keydown.escape.window="showAssignModal = false">
        <div class="fixed inset-0 min-h-dvh bg-slate-950/60 backdrop-blur-sm" @@click="showAssignModal = false"></div>
	        <div class="relative my-auto flex max-h-[92dvh] w-full max-w-xl flex-col overflow-visible rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop="driverDropdownOpen = false">
            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-orange-600 text-white shadow-xl shadow-orange-600/25">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-2xl font-black leading-tight text-slate-950">{{ $run->assignedDriver ? 'Reassign Rider' : 'Assign Rider' }}</h3>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Choose the rider responsible for this delivery run.</p>
                    </div>
                </div>
                <button @@click="showAssignModal = false"
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <!-- Modal Body -->
	            <div class="flex-1 overflow-visible px-5 py-5 sm:px-6">
	                <div>
	                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">
	                        Select Rider <span class="text-red-500">*</span>
	                    </label>
	                    <div class="relative" @@click.stop @@click.outside="driverDropdownOpen = false">
	                        <div class="relative">
	                            <input
	                                x-ref="driverSearchInput"
	                                type="search"
	                                x-model="driverSearch"
	                                @@focus="driverDropdownOpen = true"
	                                @@input="driverDropdownOpen = true; selectedDriverId = ''; selectedDriverLabel = ''"
	                                placeholder="Search rider name, phone, vehicle..."
	                                class="h-14 w-full rounded-2xl border-2 border-slate-200 bg-white px-4 pr-12 text-base font-black text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-orange-500 focus:ring-4 focus:ring-orange-100 sm:text-sm"
	                                :class="driverDropdownOpen ? 'rounded-b-none border-orange-500 ring-4 ring-orange-100' : ''"
	                            >
	                            <svg class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-transform" :class="driverDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
	                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
	                            </svg>
	                        </div>

	                        <div
	                            x-show="driverDropdownOpen"
	                            x-cloak
	                            x-transition.opacity.duration.100ms
	                            class="absolute left-0 right-0 z-40 -mt-0.5 overflow-hidden rounded-b-2xl border-2 border-t-0 border-orange-500 bg-white shadow-xl shadow-orange-900/10"
	                            style="display: none;"
	                        >
	                            <div class="max-h-72 overflow-y-auto border-t border-orange-100">
	                                <template x-for="driver in filteredDrivers()" :key="driver.id">
	                                    <button
	                                        type="button"
	                                        @@click="selectDriver(driver)"
	                                        class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-orange-50"
	                                        :class="Number(selectedDriverId) === Number(driver.id) ? 'bg-orange-50' : ''"
	                                    >
	                                        <div class="min-w-0">
	                                            <p class="truncate text-sm font-black text-slate-900" x-text="driver.name"></p>
	                                            <p class="mt-0.5 truncate text-xs font-semibold text-slate-500" x-text="driver.meta || 'No vehicle details'"></p>
	                                        </div>
	                                        <svg x-show="Number(selectedDriverId) === Number(driver.id)" class="h-4 w-4 shrink-0 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
	                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
	                                        </svg>
	                                    </button>
	                                </template>
	                                <div x-show="filteredDrivers().length === 0" class="px-4 py-6 text-center text-sm font-semibold text-slate-400">
	                                    No matching riders.
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                </div>
	            </div>
            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 rounded-b-[2rem] border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                <button @@click="showAssignModal = false"
                    class="inline-flex h-12 items-center justify-center whitespace-nowrap rounded-2xl border border-slate-200 bg-white px-5 text-sm font-black text-slate-700 transition hover:bg-slate-100 sm:h-14 sm:px-7">
                    Cancel
                </button>
                <button @@click="assignDriver()"
                    :disabled="actionLoading || !selectedDriverId"
                    class="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-orange-600 px-5 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50 sm:h-14 sm:px-8">
                    <svg x-show="actionLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="actionLoading ? 'Assigning...' : '{{ $run->assignedDriver ? "Reassign Rider" : "Assign Rider" }}'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Dispatch Confirmation Modal -->
    <div x-show="showDispatchConfirm" x-cloak
         class="fixed inset-0 z-[220] flex min-h-dvh w-screen items-center justify-center overflow-y-auto p-3 sm:p-4"
         @@keydown.escape.window="showDispatchConfirm = false">
        <div class="fixed inset-0 min-h-dvh bg-slate-950/60 backdrop-blur-sm" @@click="showDispatchConfirm = false"></div>
        <div class="relative my-auto w-full max-w-lg overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl" @@click.stop>
            <div class="px-6 py-7 text-center">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-600 text-white shadow-xl shadow-emerald-600/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
                <h3 class="mb-2 text-2xl font-black text-slate-950">Dispatch Delivery Run?</h3>
                <p class="mb-7 text-sm font-semibold leading-6 text-slate-500">
                    This will dispatch <span class="font-semibold text-slate-700">{{ $run->run_number }}</span> and the assigned rider will be notified to begin deliveries.
                </p>
                <div class="flex items-center justify-center gap-3">
                    <button @@click="showDispatchConfirm = false"
                        class="inline-flex h-12 items-center justify-center whitespace-nowrap rounded-2xl border border-slate-200 bg-white px-5 text-sm font-black text-slate-700 transition hover:bg-slate-100 sm:h-14 sm:px-7">
                        Cancel
                    </button>
                    <button @@click="dispatchRun()"
                        :disabled="actionLoading"
                        class="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-emerald-600 px-5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 sm:h-14 sm:px-8">
                        <svg x-show="actionLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="actionLoading ? 'Dispatching...' : 'Yes, Dispatch'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('head-scripts')
<script>
function adminDeliveryRunPageFallback() {
    const normalizePhoto = (photo) => {
        const value = (typeof photo === 'string' || typeof photo === 'number') ? String(photo).trim() : '';
        return value;
    };

    return {
        actionLoading: false,
        showAssignModal: false,
        showDispatchConfirm: false,
        showNotesModal: false,
        showHandoffActionModal: false,
        handoffActionType: 'delivered',
        handoffActionStop: null,
        handoffActionNotes: '',
        proofViewer: {
            open: false,
            title: '',
            subtitle: '',
            run: '',
            stopNumber: null,
            photos: [],
            index: 0,
        },
        selectedDriverId: '',

        confirmDispatch() {
            this.showDispatchConfirm = true;
        },

        async assignDriver() { this.actionLoading = false; },
        async dispatchRun() { this.actionLoading = false; },
        async resendCode() { this.actionLoading = false; },
        async submitHandoffAction() { this.actionLoading = false; },

        openHandoffAction(stop, action) {
            this.handoffActionStop = stop;
            this.handoffActionType = action;
            this.handoffActionNotes = '';
            this.showHandoffActionModal = true;
        },

        handoffActionTitle() {
            if (this.handoffActionType === 'failed') return 'Mark Handoff Failed';
            if (this.handoffActionType === 'pending') return 'Mark Pending';
            return 'Confirm Delivered';
        },

        handoffActionDescription() {
            if (this.handoffActionType === 'failed') return 'Record that the recipient did not receive this bus handoff.';
            if (this.handoffActionType === 'pending') return 'Return this handoff to pending confirmation so it can be resolved again.';
            return 'Confirm the recipient received this bus handoff.';
        },

        handoffActionButtonLabel() {
            if (this.handoffActionType === 'failed') return 'Mark Failed';
            if (this.handoffActionType === 'pending') return 'Mark Pending';
            return 'Confirm Delivered';
        },

        closeHandoffAction(force = false) {
            if (this.actionLoading && !force) return;
            this.showHandoffActionModal = false;
            this.handoffActionStop = null;
            this.handoffActionNotes = '';
            this.handoffActionType = 'delivered';
        },

        openProofPhoto(payload) {
            const photos = Array.isArray(payload?.photos)
                ? payload.photos.map(normalizePhoto).filter((photo) => photo)
                : [];
            const fallbackPhoto = typeof payload?.url === 'string' ? payload.url.trim() : '';
            const imageList = photos.length ? photos : (fallbackPhoto ? [fallbackPhoto] : []);
            if (!imageList.length) {
                return;
            }

            this.proofViewer = {
                open: true,
                title: payload?.title || 'Proof photo',
                subtitle: payload?.subtitle || '',
                run: payload?.run || '',
                stopNumber: payload?.stopNumber || null,
                photos: imageList,
                index: 0,
            };
        },

        closeProofPhoto() {
            this.proofViewer.open = false;
        },

        currentProofPhoto() {
            if (!this.proofViewer.open || !this.proofViewer.photos.length) return null;
            return this.proofViewer.photos[this.proofViewer.index] || null;
        },

        nextProofPhoto() {
            if (!this.proofViewer.photos.length || this.proofViewer.photos.length === 1) return;
            this.proofViewer.index = (this.proofViewer.index + 1) % this.proofViewer.photos.length;
        },

        previousProofPhoto() {
            if (!this.proofViewer.photos.length || this.proofViewer.photos.length === 1) return;
            this.proofViewer.index = (this.proofViewer.index - 1 + this.proofViewer.photos.length) % this.proofViewer.photos.length;
        },
    };
}

function adminDeliveryRunPage() {
    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const endpoints = {
        assignDriver: @js($deliveryRunRoutes['assignDriverUrl']),
        dispatch:     @js($deliveryRunRoutes['dispatchUrl']),
        resendCode:   @js($deliveryRunRoutes['resendCodeUrlTemplate']),
    };

    return {
        ...adminDeliveryRunPageFallback(),
        async assignDriver() {
            if (!this.selectedDriverId) {
                window.showToast?.('Please select a rider first.', 'warning');
                return;
            }
            this.actionLoading = true;
            try {
                const response = await fetch(endpoints.assignDriver, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ driver_id: Number(this.selectedDriverId) }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to assign rider.');
                }
                window.showToast?.(result.message || 'Rider assigned successfully.', 'success');
                this.showAssignModal = false;
                window.location.reload();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to assign rider.', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        async dispatchRun() {
            this.actionLoading = true;
            try {
                const response = await fetch(endpoints.dispatch, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to dispatch run.');
                }
                window.showToast?.(result.message || 'Delivery run dispatched successfully.', 'success');
                this.showDispatchConfirm = false;
                window.location.reload();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to dispatch delivery run.', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        async resendCode(stopId) {
            this.actionLoading = true;
            try {
                const url = endpoints.resendCode.replace('__STOP__', stopId);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to resend OTP.');
                }
                window.showToast?.(result.message || 'OTP code resent successfully.', 'success');
                window.location.reload();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to resend OTP.', 'error');
            } finally {
                this.actionLoading = false;
            }
        },

        async submitHandoffAction() {
            if (!this.handoffActionStop?.url) return;
            this.actionLoading = true;
            try {
                const response = await fetch(this.handoffActionStop.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: this.handoffActionType,
                        notes: this.handoffActionNotes,
                    }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to update handoff.');
                }
                window.showToast?.(result.message || 'Handoff updated successfully.', 'success');
                this.closeHandoffAction(true);
                window.location.reload();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to update handoff.', 'error');
            } finally {
                this.actionLoading = false;
            }
        },
    };
}

window.adminDeliveryRunPageFallback = window.adminDeliveryRunPageFallback || adminDeliveryRunPageFallback;
window.adminDeliveryRunPage = window.adminDeliveryRunPage || adminDeliveryRunPage;
</script>
@endpush
