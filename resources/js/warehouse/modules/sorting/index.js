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
    if (!config || !config.items_endpoint || !config.batches_endpoint) return;

    window.Alpine.data('warehouseSortingPage', () => ({
        loading: false,
        items: [],
        batches: [],
        search: '',
        selectedReceiptItemIds: [],
        destinations: Array.isArray(config.destinations) ? config.destinations : [],
        dispatchModes: Array.isArray(config.dispatch_modes) ? config.dispatch_modes : [
            { value: 'transfer', label: 'Transfer to Warehouse' },
            { value: 'local_delivery', label: 'Local Delivery' },
        ],
        newBatchDispatchMode: 'transfer',
        newBatchDestinationId: '',
        canReopenBatches: Boolean(config.can_reopen_batches),

        async init() {
            await Promise.all([this.loadItems(), this.loadBatches()]);
        },

        async loadItems() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.set('per_page', '200');
                if (this.search) params.set('search', this.search);
                const response = await fetch(`${config.items_endpoint}?${params.toString()}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Failed to load eligible items.');
                this.items = Array.isArray(result.data) ? result.data : [];
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to load sorting items.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadBatches() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.set('per_page', '100');
                const response = await fetch(`${config.batches_endpoint}?${params.toString()}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Failed to load sort batches.');
                this.batches = Array.isArray(result.data) ? result.data : [];
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to load sort batches.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async createBatch() {
            if (this.newBatchDispatchMode === 'transfer' && !this.newBatchDestinationId) {
                window.showToast?.('Select destination warehouse first.', 'warning');
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
                await this.loadBatches();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to create batch.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async addSelectedToBatch(batchId) {
            if (!Array.isArray(this.selectedReceiptItemIds) || this.selectedReceiptItemIds.length === 0) {
                window.showToast?.('Select items to add first.', 'warning');
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(withBatch(config.add_items_endpoint, batchId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        warehouse_receipt_item_ids: this.selectedReceiptItemIds.map((id) => Number(id)),
                    }),
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Failed to add items.');
                window.showToast?.(result.message || 'Items added to batch.', 'success');
                this.selectedReceiptItemIds = [];
                await Promise.all([this.loadItems(), this.loadBatches()]);
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to add items.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async removeItem(batchId, shipmentItemId) {
            this.loading = true;
            try {
                const response = await fetch(withBatchAndItem(config.remove_item_endpoint, batchId, shipmentItemId), {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Failed to remove item.');
                window.showToast?.(result.message || 'Item removed from batch.', 'success');
                await Promise.all([this.loadItems(), this.loadBatches()]);
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to remove item.', 'error');
            } finally {
                this.loading = false;
            }
        },

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
                await this.loadBatches();
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
                await this.loadBatches();
            } catch (error) {
                console.error(error);
                window.showToast?.(error.message || 'Unable to reopen batch.', 'error');
            } finally {
                this.loading = false;
            }
        },
    }));
}

if (window.Alpine) {
    registerWarehouseSortingPage();
} else {
    document.addEventListener('alpine:init', registerWarehouseSortingPage);
}
