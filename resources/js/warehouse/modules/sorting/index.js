import { createRemoteTablePage } from '../shared/table-page.js';
import { parseJsonAttribute } from '../../core/config.js';

function getConfig() {
    const container = document.querySelector('[data-warehouse-sorting-config]');
    if (!container) return null;

    const config = parseJsonAttribute(container, 'data-warehouse-sorting-config', null);
    if (!config) {
        console.error('Invalid warehouse sorting config JSON');
    }
    return config;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function withBatch(urlTemplate, batchId) {
    return (urlTemplate || '').replace('__BATCH__', String(batchId));
}

function withBatchAndItem(urlTemplate, batchId, itemId) {
    return withBatch(urlTemplate, batchId).replace('__ITEM__', String(itemId));
}

function registerWarehouseSortingPage() {
    if (!window.Alpine) return;
    const config = getConfig();
    if (!config || !config.batches_endpoint) return;

    const pageConfig = {
        endpoint: config.batches_endpoint,
        defaultSort: 'created_at',
        defaultPerPage: 10,
        exportFileName: 'warehouse-sort-batches',
        printTitle: 'Warehouse Sort Batches',
        columns: [
            { key: 'batch_number', label: 'Batch #', exportLabel: 'Batch Number' },
            { key: 'dispatch_mode_label', label: 'Mode', sortable: false },
            { key: 'destination', label: 'Destination', sortable: false },
            { key: 'status', label: 'Status' },
            { key: 'items_count', label: 'Items', sortable: false },
            { key: 'created_at', label: 'Created At' },
            { key: 'actions', label: 'Actions', sortable: false },
        ],
    };

    window.Alpine.data('warehouseSortingPage', () => {
        const page = createRemoteTablePage(pageConfig);

        return {
            ...page,

            // ── Create Batch Modal ────────────────────────────────────────
            createBatchModalOpen: false,
            destinations: Array.isArray(config.destinations) ? config.destinations : [],
            dispatchModes: Array.isArray(config.dispatch_modes) ? config.dispatch_modes : [
                { value: 'transfer', label: 'Transfer to Warehouse' },
                { value: 'local_delivery', label: 'Local Delivery' },
            ],
            newBatchDispatchMode: 'transfer',
            newBatchDestinationId: '',
            canReopenBatches: Boolean(config.can_reopen_batches),

            // ── Manage Items Modal ────────────────────────────────────────
            manageModalOpen: false,
            activeBatch: null,
            activeBatchItems: [],
            eligibleItems: [],
            eligibleSearch: '',
            selectedEligibleIds: [],
            modalLoading: false,

            init() {
                this.loadData();
            },

            // ── Manage Modal ──────────────────────────────────────────────
            openManageModal(row) {
                this.activeBatch = row;
                this.activeBatchItems = Array.isArray(row.items) ? [...row.items] : [];
                this.eligibleItems = [];
                this.selectedEligibleIds = [];
                this.eligibleSearch = '';
                this.manageModalOpen = true;
                this.loadEligibleItems();
            },

            async loadEligibleItems() {
                this.modalLoading = true;
                try {
                    const params = new URLSearchParams({ per_page: '200' });
                    if (this.eligibleSearch) params.set('search', this.eligibleSearch);
                    const response = await fetch(`${config.items_endpoint}?${params.toString()}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Failed to load eligible items.');
                    this.eligibleItems = Array.isArray(result.data) ? result.data : [];
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to load items.', 'error');
                } finally {
                    this.modalLoading = false;
                }
            },

            async addSelectedToActiveBatch() {
                if (!this.activeBatch || !this.selectedEligibleIds.length) {
                    window.showToast?.('Select items to add first.', 'warning');
                    return;
                }

                this.modalLoading = true;
                try {
                    const response = await fetch(withBatch(config.add_items_endpoint, this.activeBatch.id), {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            warehouse_receipt_item_ids: this.selectedEligibleIds.map(Number),
                        }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to add items.');
                    window.showToast?.(result.message || 'Items added.', 'success');
                    this.selectedEligibleIds = [];
                    await this.refreshModalData();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to add items.', 'error');
                } finally {
                    this.modalLoading = false;
                }
            },

            async removeItemFromBatch(shipmentItemId) {
                if (!this.activeBatch) return;
                this.modalLoading = true;
                try {
                    const response = await fetch(
                        withBatchAndItem(config.remove_item_endpoint, this.activeBatch.id, shipmentItemId),
                        {
                            method: 'DELETE',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken(),
                            },
                        }
                    );
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to remove item.');
                    window.showToast?.(result.message || 'Item removed.', 'success');
                    await this.refreshModalData();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to remove item.', 'error');
                } finally {
                    this.modalLoading = false;
                }
            },

            async refreshModalData() {
                await this.loadData();
                if (this.activeBatch) {
                    const fresh = this.rows.find((r) => r.id === this.activeBatch.id);
                    if (fresh) {
                        this.activeBatch = fresh;
                        this.activeBatchItems = Array.isArray(fresh.items) ? [...fresh.items] : [];
                    }
                }
                await this.loadEligibleItems();
            },

            // ── Batch Actions ─────────────────────────────────────────────
            async sealBatch(batchId) {
                this.loading = true;
                try {
                    const response = await fetch(withBatch(config.seal_batch_endpoint, batchId), {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to seal batch.');
                    window.showToast?.(result.message || 'Batch sealed.', 'success');
                    await this.loadData();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to seal batch.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            async reopenBatch(batchId) {
                if (!this.canReopenBatches) {
                    window.showToast?.('You do not have permission to reopen batches.', 'error');
                    return;
                }
                this.loading = true;
                try {
                    const response = await fetch(withBatch(config.reopen_batch_endpoint, batchId), {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to reopen batch.');
                    window.showToast?.(result.message || 'Batch reopened.', 'success');
                    await this.loadData();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to reopen batch.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // ── Create Batch ──────────────────────────────────────────────
            async createBatch() {
                if (this.newBatchDispatchMode === 'transfer' && !this.newBatchDestinationId) {
                    window.showToast?.('Select a destination warehouse first.', 'warning');
                    return;
                }
                this.loading = true;
                try {
                    const response = await fetch(config.create_batch_endpoint, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken(),
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            dispatch_mode: this.newBatchDispatchMode,
                            destination_warehouse_id: this.newBatchDispatchMode === 'transfer'
                                ? Number(this.newBatchDestinationId)
                                : null,
                        }),
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Failed to create batch.');
                    window.showToast?.(result.message || 'Batch created.', 'success');
                    this.newBatchDestinationId = '';
                    this.newBatchDispatchMode = 'transfer';
                    this.createBatchModalOpen = false;
                    await this.loadData();
                } catch (error) {
                    console.error(error);
                    window.showToast?.(error.message || 'Unable to create batch.', 'error');
                } finally {
                    this.loading = false;
                }
            },

            // ── Helpers ───────────────────────────────────────────────────
            statusBadgeClass(status) {
                return (status || '') === 'sealed'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                    : 'border-indigo-200 bg-indigo-50 text-indigo-700';
            },
        };
    });
}

if (window.Alpine) {
    registerWarehouseSortingPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseSortingPage);
}
